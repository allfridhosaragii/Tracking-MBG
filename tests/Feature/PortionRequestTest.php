<?php

namespace Tests\Feature;

use App\Enums\PortionRequestStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\DailyPortionRequest;
use App\Models\School;
use App\Models\Sppg;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\MbgTrackBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortionRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $picSchoolA;
    protected User $picSchoolB;
    protected User $headSppg;
    protected School $schoolA;
    protected School $schoolB;
    protected School $schoolC;
    protected Sppg $sppg;

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
    }

    /**
     * 1. PIC can view portion confirmation page for own school.
     */
    public function test_pic_can_view_portion_confirmation_page_for_own_school(): void
    {
        $tomorrow = Carbon::tomorrow('Asia/Jakarta')->format('Y-m-d');

        $response = $this->actingAs($this->picSchoolA)->get(route('school.portions.index', ['date' => $tomorrow]));

        $response->assertOk();
        $response->assertSee('Konfirmasi Kebutuhan Porsi');
        $response->assertSee($this->schoolA->name);
        $response->assertSee(number_format($this->schoolA->registered_beneficiaries));
    }

    /**
     * 2. PIC saves draft.
     */
    public function test_pic_saves_draft(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->format('Y-m-d');

        $response = $this->actingAs($this->picSchoolA)->post(route('school.portions.draft'), [
            'service_date' => $targetDate,
            'present_count' => 330,
            'requested_portions' => 330,
        ]);

        $response->assertRedirect(route('school.portions.index', ['date' => $targetDate]));
        $response->assertSessionHas('status');

        $record = DailyPortionRequest::where('school_id', $this->schoolA->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals($targetDate, $record->service_date->format('Y-m-d'));
        $this->assertEquals(330, $record->present_count);
        $this->assertEquals(330, $record->requested_portions);
        $this->assertEquals(PortionRequestStatus::DRAFT, $record->status);
        $this->assertEquals($this->schoolA->registered_beneficiaries, $record->registered_count);
    }

    /**
     * 3. PIC confirms portion request before cutoff.
     */
    public function test_pic_confirms_request_before_cutoff(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(2)->format('Y-m-d');

        $portion = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'status' => PortionRequestStatus::DRAFT,
        ]);

        $response = $this->actingAs($this->picSchoolA)->post(route('school.portions.confirm', $portion), [
            'present_count' => 340,
            'requested_portions' => 340,
        ]);

        $response->assertRedirect(route('school.portions.index', ['date' => $targetDate]));
        $response->assertSessionHas('status');

        $portion->refresh();
        $this->assertEquals(PortionRequestStatus::CONFIRMED, $portion->status);
        $this->assertEquals(340, $portion->requested_portions);
        $this->assertEquals(340, $portion->present_count);
        $this->assertEquals($this->picSchoolA->id, $portion->confirmed_by);
        $this->assertNotNull($portion->confirmed_at);
    }

    /**
     * 4. PIC cannot view or mutate other school's portion requests (Tenant Isolation).
     */
    public function test_pic_cannot_view_or_mutate_other_school_portion_requests(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->format('Y-m-d');

        // School B request exists
        $portionB = DailyPortionRequest::create([
            'school_id' => $this->schoolB->id,
            'service_date' => $targetDate,
            'registered_count' => $this->schoolB->registered_beneficiaries,
            'requested_portions' => 270,
            'status' => PortionRequestStatus::CONFIRMED,
            'confirmed_by' => $this->picSchoolB->id,
            'confirmed_at' => now(),
        ]);

        // PIC A tries to confirm School B's request
        $responseConfirm = $this->actingAs($this->picSchoolA)->post(route('school.portions.confirm', $portionB), [
            'requested_portions' => 200,
        ]);
        $responseConfirm->assertStatus(403);

        // PIC A tries to revise School B's request directly
        $responseRevise = $this->actingAs($this->picSchoolA)->post(route('school.portions.revise', $portionB), [
            'requested_portions' => 200,
            'revision_reason' => 'Perubahan ilegal lintas sekolah.',
        ]);
        $responseRevise->assertStatus(403);

        // PIC A tries to cancel School B's request
        $responseCancel = $this->actingAs($this->picSchoolA)->post(route('school.portions.cancel', $portionB), [
            'reason' => 'Pembatalan ilegal lintas sekolah.',
        ]);
        $responseCancel->assertStatus(403);
    }

    /**
     * 5. Other roles cannot mutate portion requests.
     */
    public function test_other_roles_cannot_mutate_portion_requests(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->format('Y-m-d');
        $kurir = User::where('email', 'kurir@mbgtrack.test')->firstOrFail();
        $gizi = User::where('email', 'gizi@mbgtrack.test')->firstOrFail();
        $asisten = User::where('email', 'asisten@mbgtrack.test')->firstOrFail();

        $portion = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'status' => PortionRequestStatus::DRAFT,
        ]);

        foreach ([$kurir, $gizi, $asisten, $this->headSppg] as $unauthorizedUser) {
            $response = $this->actingAs($unauthorizedUser)->post(route('school.portions.draft'), [
                'service_date' => $targetDate,
                'requested_portions' => 100,
            ]);
            $response->assertStatus(403);

            $responseConfirm = $this->actingAs($unauthorizedUser)->post(route('school.portions.confirm', $portion), [
                'requested_portions' => 100,
            ]);
            $responseConfirm->assertStatus(403);
        }
    }

    /**
     * 6. Unique school_id + service_date constraint prevents duplicate entries.
     */
    public function test_unique_school_and_service_date_constraint(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->format('Y-m-d');

        DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 300,
            'status' => PortionRequestStatus::DRAFT,
        ]);
    }

    /**
     * 7. Double submit is handled safely without duplicates.
     */
    public function test_double_submit_is_handled_safely(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(3)->format('Y-m-d');

        $portion = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'status' => PortionRequestStatus::DRAFT,
        ]);

        // First submit
        $this->actingAs($this->picSchoolA)->post(route('school.portions.confirm', $portion), [
            'present_count' => 340,
            'requested_portions' => 340,
        ])->assertRedirect();

        // Second submit (same request)
        $this->actingAs($this->picSchoolA)->post(route('school.portions.confirm', $portion), [
            'present_count' => 340,
            'requested_portions' => 340,
        ])->assertRedirect();

        $count = DailyPortionRequest::where('school_id', $this->schoolA->id)
            ->whereDate('service_date', $targetDate)
            ->count();

        $this->assertEquals(1, $count);
    }

    /**
     * 8. Negative numbers are rejected by validation.
     */
    public function test_negative_numbers_are_rejected(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->format('Y-m-d');

        $response = $this->actingAs($this->picSchoolA)->post(route('school.portions.draft'), [
            'service_date' => $targetDate,
            'present_count' => -5,
            'requested_portions' => -10,
        ]);

        $response->assertSessionHasErrors(['present_count', 'requested_portions']);
    }

    /**
     * 9. Null values differ from zero (unknown vs explicit zero).
     */
    public function test_null_value_differs_from_zero(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->format('Y-m-d');

        // Saving draft with null values (yet to be filled)
        $this->actingAs($this->picSchoolA)->post(route('school.portions.draft'), [
            'service_date' => $targetDate,
            'present_count' => null,
            'requested_portions' => null,
        ])->assertRedirect();

        $record = DailyPortionRequest::where('school_id', $this->schoolA->id)
            ->whereDate('service_date', $targetDate)
            ->first();

        $this->assertNull($record->present_count);
        $this->assertNull($record->requested_portions);

        // Now saving with explicit 0
        $this->actingAs($this->picSchoolA)->post(route('school.portions.draft'), [
            'service_date' => $targetDate,
            'present_count' => 0,
            'requested_portions' => 0,
        ])->assertRedirect();

        $record->refresh();
        $this->assertSame(0, $record->present_count);
        $this->assertSame(0, $record->requested_portions);
    }

    /**
     * 10. Requested portions exceeding registered count requires justification reason.
     */
    public function test_requested_portions_exceeding_registered_count_requires_justification(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(4)->format('Y-m-d');

        $portion = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'status' => PortionRequestStatus::DRAFT,
        ]);

        // Attempting to request 360 without justification
        $responseWithoutReason = $this->actingAs($this->picSchoolA)->post(route('school.portions.confirm', $portion), [
            'present_count' => 360,
            'requested_portions' => 360,
            'revision_reason' => '',
        ]);

        $responseWithoutReason->assertSessionHasErrors('revision_reason');

        // With explicit justification reason
        $responseWithReason = $this->actingAs($this->picSchoolA)->post(route('school.portions.confirm', $portion), [
            'present_count' => 360,
            'requested_portions' => 360,
            'revision_reason' => 'Tambahan 20 porsi siswa pindahan baru yang berkasnya sedang diverifikasi dinas.',
        ]);

        $responseWithReason->assertSessionHasNoErrors();
        $portion->refresh();
        $this->assertEquals(360, $portion->requested_portions);
        $this->assertEquals(PortionRequestStatus::CONFIRMED, $portion->status);
    }

    /**
     * 11. Cutoff enforcement blocks standard confirmation after cutoff.
     */
    public function test_cutoff_enforcement_blocks_standard_confirmation(): void
    {
        // Service date today means cutoff was yesterday 16:00 WIB
        $today = Carbon::today('Asia/Jakarta')->format('Y-m-d');

        $portion = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $today,
            'registered_count' => 340,
            'status' => PortionRequestStatus::DRAFT,
        ]);

        $response = $this->actingAs($this->picSchoolA)->post(route('school.portions.confirm', $portion), [
            'present_count' => 340,
            'requested_portions' => 340,
        ]);

        $response->assertSessionHasErrors('cutoff');
    }

    /**
     * 12. Revision with reason succeeds.
     */
    public function test_revision_with_reason_succeeds(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(2)->format('Y-m-d');

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

        $response = $this->actingAs($this->picSchoolA)->post(route('school.portions.revise', $portion), [
            'requested_portions' => 320,
            'present_count' => 320,
            'revision_reason' => 'Pengurangan 20 siswa kelas 6 mengikuti ujian di luar sekolah.',
        ]);

        $response->assertRedirect(route('school.portions.index', ['date' => $targetDate]));
        $response->assertSessionHas('status');

        $portion->refresh();
        $this->assertEquals(PortionRequestStatus::REVISED, $portion->status);
        $this->assertEquals(320, $portion->requested_portions);
        $this->assertEquals('Pengurangan 20 siswa kelas 6 mengikuti ujian di luar sekolah.', $portion->revision_reason);
    }

    /**
     * 13. Revision without reason is rejected.
     */
    public function test_revision_without_reason_is_rejected(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(2)->format('Y-m-d');

        $portion = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
            'confirmed_by' => $this->picSchoolA->id,
            'confirmed_at' => now(),
        ]);

        $response = $this->actingAs($this->picSchoolA)->post(route('school.portions.revise', $portion), [
            'requested_portions' => 320,
            'revision_reason' => '',
        ]);

        $response->assertSessionHasErrors('revision_reason');
    }

    /**
     * 14. Service cancellation with reason succeeds.
     */
    public function test_service_cancellation_with_reason_succeeds(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(5)->format('Y-m-d');

        $portion = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
            'confirmed_by' => $this->picSchoolA->id,
            'confirmed_at' => now(),
        ]);

        $response = $this->actingAs($this->picSchoolA)->post(route('school.portions.cancel', $portion), [
            'reason' => 'Sekolah libur nasional peringatan hari besar daerah.',
        ]);

        $response->assertRedirect(route('school.portions.index', ['date' => $targetDate]));
        $response->assertSessionHas('status');

        $portion->refresh();
        $this->assertEquals(PortionRequestStatus::CANCELLED, $portion->status);
        $this->assertEquals('Sekolah libur nasional peringatan hari besar daerah.', $portion->revision_reason);
    }

    /**
     * 15. Service cancellation without reason is rejected.
     */
    public function test_service_cancellation_without_reason_is_rejected(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(5)->format('Y-m-d');

        $portion = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
            'confirmed_by' => $this->picSchoolA->id,
            'confirmed_at' => now(),
        ]);

        $response = $this->actingAs($this->picSchoolA)->post(route('school.portions.cancel', $portion), [
            'reason' => '',
        ]);

        $response->assertSessionHasErrors('reason');
    }

    /**
     * 16. Inactive PIC is blocked.
     */
    public function test_inactive_pic_is_blocked(): void
    {
        $this->picSchoolA->update(['is_active' => false]);

        $response = $this->actingAs($this->picSchoolA)->get(route('school.portions.index'));

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }

    /**
     * 17. Head SPPG can monitor aggregate and pending schools are not assumed zero.
     * Specific prompt scenario:
     * School A: 340, CONFIRMED
     * School B: Pending
     * School C: 410, CONFIRMED
     * Expected: Total confirmed = 750, Pending = 1, School B is NOT 0.
     */
    public function test_head_sppg_views_aggregate_and_pending_schools_are_not_assumed_zero(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->format('Y-m-d');

        // School A confirmed: 340
        DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'present_count' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
            'confirmed_by' => $this->picSchoolA->id,
            'confirmed_at' => now(),
        ]);

        // School B: NOT CONFIRMED (No row, or DRAFT only)
        // Leave School B empty!

        // School C confirmed: 410
        DailyPortionRequest::create([
            'school_id' => $this->schoolC->id,
            'service_date' => $targetDate,
            'registered_count' => 410,
            'requested_portions' => 410,
            'present_count' => 410,
            'status' => PortionRequestStatus::CONFIRMED,
            'confirmed_by' => $this->picSchoolA->id,
            'confirmed_at' => now(),
        ]);

        $response = $this->actingAs($this->headSppg)->get(route('head.portions.index', ['date' => $targetDate]));

        $response->assertOk();
        $response->assertSee('750'); // 340 + 410
        $response->assertSee('Menunggu Konfirmasi PIC');
        $response->assertSee($this->schoolB->name);
        // Explicitly assert invariant warning is displayed
        $response->assertSee('Agregasi Parsial');
        $response->assertSee('TIDAK dihitung nol porsi');
    }

    /**
     * 18. Cross SPPG isolation for Head SPPG.
     */
    public function test_cross_sppg_isolation_for_head_monitoring(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->format('Y-m-d');

        // Create second SPPG and school
        $sppgOther = Sppg::create([
            'code' => 'SPPG-MDN-01',
            'name' => 'SPPG Medan Kota',
            'address' => 'Jl. Balai Kota No. 1, Medan',
            'latitude' => 3.5952,
            'longitude' => 98.6722,
            'max_daily_portions' => 1000,
            'is_active' => true,
        ]);

        $schoolOther = School::create([
            'sppg_id' => $sppgOther->id,
            'school_code' => 'SCH-MEDAN-01',
            'name' => 'SDN 01 Medan Kota',
            'education_level' => 'SD',
            'address' => 'Jl. Sudirman Medan',
            'latitude' => 3.5952,
            'longitude' => 98.6722,
            'registered_beneficiaries' => 500,
            'public_slug' => 'sdn-01-medan-kota',
            'is_active' => true,
        ]);

        DailyPortionRequest::create([
            'school_id' => $schoolOther->id,
            'service_date' => $targetDate,
            'registered_count' => 500,
            'requested_portions' => 500,
            'status' => PortionRequestStatus::CONFIRMED,
        ]);

        // Head of SPPG Surabaya accesses monitoring
        $response = $this->actingAs($this->headSppg)->get(route('head.portions.index', ['date' => $targetDate]));

        $response->assertOk();
        // Should NOT see Medan school or its 500 portions
        $response->assertDontSee('SCH-MEDAN-01');
        $response->assertDontSee('SDN 01 Medan Kota');
    }

    /**
     * 19. Activity logs recorded for all portion actions.
     */
    public function test_activity_logs_recorded_for_portion_actions(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(3)->format('Y-m-d');

        $portion = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'status' => PortionRequestStatus::DRAFT,
        ]);

        // Confirm
        $this->actingAs($this->picSchoolA)->post(route('school.portions.confirm', $portion), [
            'present_count' => 340,
            'requested_portions' => 340,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'portion_request.confirmed',
            'auditable_type' => DailyPortionRequest::class,
            'auditable_id' => $portion->id,
            'actor_id' => $this->picSchoolA->id,
        ]);

        // Revise
        $this->actingAs($this->picSchoolA)->post(route('school.portions.revise', $portion), [
            'requested_portions' => 335,
            'revision_reason' => 'Pengurangan 5 porsi siswa sakit berhalangan.',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'portion_request.revised',
            'auditable_type' => DailyPortionRequest::class,
            'auditable_id' => $portion->id,
            'actor_id' => $this->picSchoolA->id,
        ]);

        // Cancel
        $this->actingAs($this->picSchoolA)->post(route('school.portions.cancel', $portion), [
            'reason' => 'Kegiatan outbond dibatalkan sepihak.',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'portion_request.cancelled',
            'auditable_type' => DailyPortionRequest::class,
            'auditable_id' => $portion->id,
            'actor_id' => $this->picSchoolA->id,
        ]);
    }
}
