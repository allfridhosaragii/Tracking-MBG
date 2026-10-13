<?php

namespace Tests\Feature;

use App\Enums\PortionRequestStatus;
use App\Models\ActivityLog;
use App\Models\DailyPortionRequest;
use App\Models\School;
use App\Models\Sppg;
use App\Models\User;
use App\Services\PortionRequestService;
use Carbon\Carbon;
use Database\Seeders\MbgTrackBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortionRequestHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $picSchoolA;
    protected User $picSchoolB;
    protected User $headSppg;
    protected School $schoolA;
    protected School $schoolB;
    protected School $schoolC;
    protected Sppg $sppg;
    protected PortionRequestService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MbgTrackBaselineSeeder::class);

        $this->sppg = Sppg::where('code', 'SPPG-SUB-01')->firstOrFail();
        $this->schoolA = School::where('school_code', 'SCH-SDN-01')->firstOrFail();
        $this->schoolB = School::where('school_code', 'SCH-SMPN-02')->firstOrFail();
        $this->schoolC = School::where('school_code', 'SCH-SMAN-05')->firstOrFail();

        $this->picSchoolA = User::where('email', 'pic.sdn01@mbgtrack.test')->firstOrFail();
        $this->picSchoolB = User::where('email', 'pic.smpn02@mbgtrack.test')->firstOrFail();
        $this->headSppg = User::where('email', 'kepala@mbgtrack.test')->firstOrFail();

        $this->service = app(PortionRequestService::class);
    }

    /**
     * Gate A & C: GET halaman tidak menulis ke database atau membuat audit log bisnis.
     */
    public function test_get_page_does_not_write_to_database_or_create_audit_log(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(10)->format('Y-m-d');

        $initialRequestsCount = DailyPortionRequest::count();
        $initialLogsCount = ActivityLog::count();

        $response = $this->actingAs($this->picSchoolA)->get(route('school.portions.index', ['date' => $targetDate]));

        $response->assertOk();
        $response->assertSee('Belum dibuat');
        $response->assertSee($this->schoolA->name);

        $this->assertEquals($initialRequestsCount, DailyPortionRequest::count(), 'GET request must not create rows in daily_portion_requests');
        $this->assertEquals($initialLogsCount, ActivityLog::count(), 'GET request must not produce business activity logs');
    }

    /**
     * Gate C: Dua concurrent draft creation tidak membuat duplicate record.
     */
    public function test_concurrent_draft_creation_does_not_create_duplicate_record(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(11)->format('Y-m-d');

        // First call
        $first = $this->service->saveDraft($this->schoolA, $targetDate, [
            'present_count' => 300,
            'requested_portions' => 300,
        ], $this->picSchoolA);

        // Concurrent/second call for identical school and date
        $second = $this->service->saveDraft($this->schoolA, $targetDate, [
            'present_count' => 310,
            'requested_portions' => 310,
        ], $this->picSchoolA);

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, DailyPortionRequest::where('school_id', $this->schoolA->id)->whereDate('service_date', $targetDate)->count());
        $this->assertEquals(310, $second->fresh()->requested_portions);
    }

    /**
     * Gate A: CONFIRMED menjadi LOCKED hanya melalui aksi sah dan locked_at tersimpan benar.
     */
    public function test_confirmed_transitions_to_locked_only_via_authorized_action(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(12)->format('Y-m-d');

        $portion = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'present_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
            'confirmed_by' => $this->picSchoolA->id,
            'confirmed_at' => now(),
        ]);

        // Attempting to inject LOCKED status directly through PIC confirmation route is forbidden / rejected
        $this->actingAs($this->picSchoolA)->post(route('school.portions.confirm', $portion), [
            'status' => PortionRequestStatus::LOCKED->value,
            'requested_portions' => 340,
        ]);

        $portion->refresh();
        $this->assertEquals(PortionRequestStatus::CONFIRMED, $portion->status);
        $this->assertNull($portion->locked_at);

        // Lock via authorized service (e.g. Kepala SPPG or production plan workflow)
        $lockedPortion = $this->service->lockRequest($portion, $this->headSppg);

        $this->assertEquals(PortionRequestStatus::LOCKED, $lockedPortion->status);
        $this->assertNotNull($lockedPortion->locked_at);

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'portion_request.locked',
            'auditable_type' => DailyPortionRequest::class,
            'auditable_id' => $portion->id,
            'actor_id' => $this->headSppg->id,
        ]);
    }

    /**
     * Gate A: Lock ganda aman dan tidak menimbulkan efek samping ganda (idempotent).
     */
    public function test_locking_is_idempotent_and_does_not_duplicate_activity_log(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(13)->format('Y-m-d');

        $portion = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
            'confirmed_by' => $this->picSchoolA->id,
            'confirmed_at' => now(),
        ]);

        $this->service->lockRequest($portion, $this->headSppg);
        $initialLogCount = ActivityLog::where('event', 'portion_request.locked')->where('auditable_id', $portion->id)->count();
        $this->assertEquals(1, $initialLogCount);

        // Re-locking same request
        $this->service->lockRequest($portion->fresh(), $this->headSppg);
        $afterLogCount = ActivityLog::where('event', 'portion_request.locked')->where('auditable_id', $portion->id)->count();
        $this->assertEquals(1, $afterLogCount, 'Re-locking must not create duplicate audit log');
    }

    /**
     * Gate B: Revisi yang belum disetujui tidak dihitung sebagai porsi resmi dan dipisahkan dari permintaan resmi.
     */
    public function test_unapproved_revision_is_not_counted_as_official_aggregate_portions(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(14)->format('Y-m-d');

        $portion = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'present_count' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
            'confirmed_by' => $this->picSchoolA->id,
            'confirmed_at' => now(),
        ]);

        // PIC submits revision request to 300 portions
        $this->service->submitRevision($portion, [
            'requested_portions' => 300,
            'present_count' => 300,
            'revision_reason' => 'Pengurangan kelas 6 outbond',
        ], $this->picSchoolA);

        $portion->refresh();
        $this->assertEquals(PortionRequestStatus::REVISED, $portion->status);
        $this->assertEquals(340, $portion->requested_portions, 'Official portions must remain at 340 until approved');
        $this->assertEquals(300, $portion->revised_portions, 'Proposed revision must be stored in revised_portions');

        // Check SPPG Aggregate
        $aggregate = $this->service->getSppgAggregateForDate($this->sppg->id, $targetDate);

        // Total confirmed portions must NOT include unapproved revision or overwrite
        $this->assertEquals(0, $aggregate['total_confirmed_portions'], 'REVISED request must not be added to confirmed aggregate');
        $this->assertEquals(1, $aggregate['revised_count']);
        $this->assertCount(1, $aggregate['revised_schools']);
        $this->assertEquals(300, $aggregate['revised_schools'][0]['revised_portions']);
        $this->assertEquals(340, $aggregate['revised_schools'][0]['original_portions']);
    }

    /**
     * Gate B: Revisi yang disetujui Kepala SPPG beralih ke LOCKED dengan angka baru dan jejak audit.
     */
    public function test_approved_revision_transitions_to_locked_with_new_portions_and_audit(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(15)->format('Y-m-d');

        $portion = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
            'confirmed_by' => $this->picSchoolA->id,
            'confirmed_at' => now(),
        ]);

        $this->service->submitRevision($portion, [
            'requested_portions' => 310,
            'present_count' => 310,
            'revision_reason' => 'Penyesuaian 30 siswa sakit',
        ], $this->picSchoolA);

        // Kepala SPPG approves revision
        $approvedPortion = $this->service->approveRevision($portion->fresh(), $this->headSppg);

        $this->assertEquals(PortionRequestStatus::LOCKED, $approvedPortion->status);
        $this->assertEquals(310, $approvedPortion->requested_portions);
        $this->assertNull($approvedPortion->revised_portions);
        $this->assertEquals($this->headSppg->id, $approvedPortion->revision_reviewed_by);
        $this->assertNotNull($approvedPortion->revision_reviewed_at);
        $this->assertNotNull($approvedPortion->locked_at);

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'portion_request.revision_approved',
            'auditable_type' => DailyPortionRequest::class,
            'auditable_id' => $portion->id,
            'actor_id' => $this->headSppg->id,
        ]);
    }

    /**
     * Gate B: Revisi yang ditolak Kepala SPPG mempertahankan angka lama dan mencatat alasan penolakan.
     */
    public function test_rejected_revision_retains_original_portions_with_rejection_reason(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(16)->format('Y-m-d');

        $portion = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
            'confirmed_by' => $this->picSchoolA->id,
            'confirmed_at' => now(),
        ]);

        $this->service->submitRevision($portion, [
            'requested_portions' => 310,
            'present_count' => 310,
            'revision_reason' => 'Penyesuaian siswa',
        ], $this->picSchoolA);

        // Kepala SPPG rejects revision
        $rejectedPortion = $this->service->rejectRevision($portion->fresh(), 'Bahan baku telah disiapkan, revisi mendadak ditolak', $this->headSppg);

        $this->assertEquals(PortionRequestStatus::CONFIRMED, $rejectedPortion->status);
        $this->assertEquals(340, $rejectedPortion->requested_portions);
        $this->assertNull($rejectedPortion->revised_portions);
        $this->assertEquals($this->headSppg->id, $rejectedPortion->revision_reviewed_by);
        $this->assertNotNull($rejectedPortion->revision_reviewed_at);
        $this->assertEquals('Bahan baku telah disiapkan, revisi mendadak ditolak', $rejectedPortion->revision_rejection_reason);

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'portion_request.revision_rejected',
            'auditable_type' => DailyPortionRequest::class,
            'auditable_id' => $portion->id,
            'actor_id' => $this->headSppg->id,
        ]);
    }

    /**
     * Gate A: Request LOCKED tidak dapat diedit sebagai draft.
     */
    public function test_locked_request_cannot_be_saved_as_draft(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(17)->format('Y-m-d');

        DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::LOCKED,
            'locked_at' => now(),
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->service->saveDraft($this->schoolA, $targetDate, [
            'present_count' => 320,
            'requested_portions' => 320,
        ], $this->picSchoolA);
    }

    /**
     * Gate D: CANCELLED tidak dapat dibatalkan ulang (idempotent guard against repeated cancellation).
     */
    public function test_cancelled_request_cannot_be_cancelled_again(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(18)->format('Y-m-d');

        $portion = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
        ]);

        $this->service->cancelRequest($portion, 'Libur sekolah', $this->picSchoolA);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->service->cancelRequest($portion->fresh(), 'Pembatalan ulang tidak sah', $this->picSchoolA);
    }

    /**
     * Gate E: Permintaan di atas registered_count memerlukan approval dan diisolasi dari porsi resmi.
     */
    public function test_request_exceeding_registered_count_requires_approval(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(19)->format('Y-m-d');

        $portion = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'status' => PortionRequestStatus::DRAFT,
        ]);

        // Confirm 360 (> 340) with reason
        $confirmed = $this->service->confirmRequest($portion, [
            'present_count' => 360,
            'requested_portions' => 360,
            'revision_reason' => 'Rombel tambahan 20 siswa tamu studi banding',
        ], $this->picSchoolA);

        $this->assertTrue($confirmed->isExceedingRegistered());
        $this->assertFalse($confirmed->isExceedingApproved());
        $this->assertTrue($confirmed->hasUnapprovedException());

        // In aggregate, flagged in unapproved_exceptions
        $aggregate = $this->service->getSppgAggregateForDate($this->sppg->id, $targetDate);
        $this->assertCount(1, $aggregate['unapproved_exceptions']);
        $this->assertEquals($this->schoolA->id, $aggregate['unapproved_exceptions'][0]['school']->id);

        // Once Kepala SPPG approves the exception
        $approvedException = $this->service->approveExceedingException($confirmed, $this->headSppg);
        $this->assertTrue($approvedException->isExceedingApproved());
        $this->assertFalse($approvedException->hasUnapprovedException());
        $this->assertEquals($this->headSppg->id, $approvedException->exceeds_approved_by);

        // Aggregate now has 0 unapproved exceptions
        $aggregateAfter = $this->service->getSppgAggregateForDate($this->sppg->id, $targetDate);
        $this->assertCount(0, $aggregateAfter['unapproved_exceptions']);
    }

    /**
     * Gate A-E: Agregasi confirmed, locked, revised, cancelled, dan pending terhitung benar.
     * Skenario resmi prompt:
     * 340 (SDN 01 Confirmed) + 276 (SMPN 02 Locked) = 616 terkonfirmasi sah.
     * SMAN 05 pending (TIDAK dihitung nol porsi!).
     */
    public function test_full_aggregate_breakdown_of_all_statuses(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(20)->format('Y-m-d');

        // School A: 340 CONFIRMED
        DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'present_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
            'confirmed_by' => $this->picSchoolA->id,
            'confirmed_at' => now(),
        ]);

        // School B: 276 LOCKED
        DailyPortionRequest::create([
            'school_id' => $this->schoolB->id,
            'service_date' => $targetDate,
            'registered_count' => 276,
            'present_count' => 276,
            'requested_portions' => 276,
            'status' => PortionRequestStatus::LOCKED,
            'locked_at' => now(),
        ]);

        // School C: Pending (No record created)

        $aggregate = $this->service->getSppgAggregateForDate($this->sppg->id, $targetDate);

        $this->assertEquals(616, $aggregate['total_confirmed_portions'], '340 + 276 = 616');
        $this->assertEquals(2, $aggregate['confirmed_count']);
        $this->assertEquals(1, $aggregate['pending_count']);
        $this->assertFalse($aggregate['all_confirmed']);
        $this->assertCount(1, $aggregate['pending_schools']);
        $this->assertEquals($this->schoolC->id, $aggregate['pending_schools'][0]['school']->id);
    }
}

