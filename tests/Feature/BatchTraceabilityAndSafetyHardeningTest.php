<?php

namespace Tests\Feature;

use App\Enums\FoodBatchStatus;
use App\Enums\MenuStatus;
use App\Enums\PortionRequestStatus;
use App\Enums\ProductionPlanStatus;
use App\Enums\ProductionStage;
use App\Enums\QualityCheckConclusion;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\DailyPortionRequest;
use App\Models\DeliveryItem;
use App\Models\EvidencePhoto;
use App\Models\FoodBatch;
use App\Models\Menu;
use App\Models\ProductionAllocation;
use App\Models\ProductionPlan;
use App\Models\QualityCheck;
use App\Models\School;
use App\Models\Sppg;
use App\Models\User;
use App\Services\FoodBatchService;
use App\Services\QualityControlService;
use Carbon\Carbon;
use Database\Seeders\MbgTrackBaselineSeeder;
use Database\Seeders\MenuBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BatchTraceabilityAndSafetyHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected Sppg $sppgA;
    protected Sppg $sppgB;
    protected School $schoolA1;
    protected School $schoolA2;
    protected School $schoolB1;
    protected Menu $menuA;
    protected User $asistenA;
    protected User $giziA;
    protected User $kepalaA;
    protected User $asistenB;
    protected User $giziB;
    protected User $driverA;
    protected ProductionPlan $planA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MbgTrackBaselineSeeder::class);
        $this->seed(MenuBaselineSeeder::class);

        $this->sppgA = Sppg::where('code', 'SPPG-SUB-01')->firstOrFail();
        $this->sppgB = Sppg::firstOrCreate(
            ['code' => 'SPPG-SUB-02'],
            [
                'name' => 'SPPG Kedua Malang (DEMO/SIMULASI)',
                'address' => 'Jl. Ijen No. 10, Malang, Jawa Timur',
                'max_daily_portions' => 1000,
                'is_active' => true,
            ]
        );

        $this->schoolA1 = School::where('school_code', 'SCH-SDN-01')->firstOrFail();
        $this->schoolA2 = School::where('school_code', 'SCH-SMPN-02')->firstOrFail();
        $this->schoolB1 = School::where('school_code', 'SCH-SMAN-05')->firstOrFail();

        $this->asistenA = User::where('email', 'asisten@mbgtrack.test')->firstOrFail();
        $this->giziA = User::where('email', 'gizi@mbgtrack.test')->firstOrFail();
        $this->kepalaA = User::where('email', 'kepala@mbgtrack.test')->firstOrFail();
        $this->driverA = User::where('email', 'kurir@mbgtrack.test')->firstOrFail();

        // Create secondary staff for SPPG B (tenant isolation testing)
        $this->asistenB = User::create([
            'sppg_id' => $this->sppgB->id,
            'name' => 'Asisten B',
            'email' => 'asisten.b@mbgtrack.test',
            'password' => bcrypt('password'),
            'role' => UserRole::ASISTEN_LAPANGAN,
            'is_active' => true,
        ]);

        $this->giziB = User::create([
            'sppg_id' => $this->sppgB->id,
            'name' => 'Gizi B',
            'email' => 'gizi.b@mbgtrack.test',
            'password' => bcrypt('password'),
            'role' => UserRole::PENGAWAS_GIZI,
            'is_active' => true,
        ]);

        $this->menuA = Menu::where('sppg_id', $this->sppgA->id)
            ->where('status', MenuStatus::ACTIVE)
            ->firstOrFail();

        // Create a finalized production plan for SPPG A: 600 total portions (School A1: 350, School A2: 250)
        $date = Carbon::tomorrow();

        $req1 = DailyPortionRequest::create([
            'school_id' => $this->schoolA1->id,
            'sppg_id' => $this->sppgA->id,
            'service_date' => $date,
            'registered_count' => 350,
            'requested_portions' => 350,
            'status' => PortionRequestStatus::CONFIRMED,
            'created_by' => $this->kepalaA->id,
            'confirmed_at' => now(),
        ]);

        $req2 = DailyPortionRequest::create([
            'school_id' => $this->schoolA2->id,
            'sppg_id' => $this->sppgA->id,
            'service_date' => $date,
            'registered_count' => 250,
            'requested_portions' => 250,
            'status' => PortionRequestStatus::CONFIRMED,
            'created_by' => $this->kepalaA->id,
            'confirmed_at' => now(),
        ]);

        $this->planA = ProductionPlan::create([
            'sppg_id' => $this->sppgA->id,
            'service_date' => $date,
            'menu_id' => $this->menuA->id,
            'status' => ProductionPlanStatus::FINALIZED,
            'finalized_by' => $this->kepalaA->id,
            'finalized_at' => now(),
            'created_by' => $this->kepalaA->id,
        ]);

        ProductionAllocation::create([
            'production_plan_id' => $this->planA->id,
            'portion_request_id' => $req1->id,
            'school_id' => $this->schoolA1->id,
            'allocated_portions' => 350,
        ]);

        ProductionAllocation::create([
            'production_plan_id' => $this->planA->id,
            'portion_request_id' => $req2->id,
            'school_id' => $this->schoolA2->id,
            'allocated_portions' => 250,
        ]);
    }

    /**
     * 1. Test: FOOD_READY photo is normally required when submitting batch to QC.
     */
    public function test_food_ready_photo_is_normally_required_on_submit_qc(): void
    {
        $this->actingAs($this->asistenA)
            ->post(route('production.plans.batches.store', $this->planA), [
                'planned_portions' => 350,
            ]);

        $batch = FoodBatch::where('production_plan_id', $this->planA->id)->firstOrFail();

        $this->actingAs($this->asistenA)
            ->post(route('production.batches.actual', $batch), [
                'actual_portions' => 350,
            ]);

        // Submit without photo and without exception -> must be rejected
        $response = $this->actingAs($this->asistenA)
            ->post(route('production.batches.submit-qc', $batch), [
                'notes' => 'Tanpa foto bukti',
            ]);

        $response->assertSessionHasErrors('photo');
        $this->assertEquals(FoodBatchStatus::PREPARING, $batch->fresh()->status);
    }

    /**
     * 2. Test: Reasoned exception flow succeeds when camera/upload fails.
     */
    public function test_food_ready_photo_exception_flow_with_valid_reason(): void
    {
        $this->actingAs($this->asistenA)
            ->post(route('production.plans.batches.store', $this->planA), [
                'planned_portions' => 350,
            ]);

        $batch = FoodBatch::where('production_plan_id', $this->planA->id)->firstOrFail();

        $this->actingAs($this->asistenA)
            ->post(route('production.batches.actual', $batch), [
                'actual_portions' => 350,
            ]);

        // Submitting with reasoned exception (min 15 chars)
        $response = $this->actingAs($this->asistenA)
            ->post(route('production.batches.submit-qc', $batch), [
                'has_evidence_exception' => 1,
                'evidence_exception_reason' => 'Perangkat kamera ponsel staf dapur rusak saat akhir pengemasan.',
                'notes' => 'Pengecualian diajukan kru',
            ]);

        $response->assertRedirect(route('production.plans.execute', $this->planA->id));

        $batch->refresh();
        $this->assertEquals(FoodBatchStatus::QC_CHECK, $batch->status);
        $this->assertTrue($batch->has_evidence_exception);
        $this->assertEquals('Perangkat kamera ponsel staf dapur rusak saat akhir pengemasan.', $batch->evidence_exception_reason);
        $this->assertEquals($this->asistenA->id, $batch->evidence_exception_requested_by);
        $this->assertEquals('PENDING', $batch->evidence_exception_status);
        $this->assertNull($batch->evidence_exception_approved_by);
        $this->assertFalse($batch->hasValidEvidence()); // Pending exception cannot be evaluated

        // Authorized supervisor (Pengawas Gizi) reviews and approves the exception
        app(FoodBatchService::class)->approveEvidenceException($batch, $this->giziA, 'Disetujui berdasarkan verifikasi kendala kamera dapur.');

        $batch->refresh();
        $this->assertEquals('APPROVED', $batch->evidence_exception_status);
        $this->assertEquals($this->giziA->id, $batch->evidence_exception_approved_by);
        $this->assertTrue($batch->hasValidEvidence());
    }

    /**
     * 3. Test: Uploading fake MIME file disguised as image is rejected.
     */
    public function test_fake_mime_type_upload_rejected(): void
    {
        Storage::fake('local');

        $this->actingAs($this->asistenA)
            ->post(route('production.plans.batches.store', $this->planA), [
                'planned_portions' => 350,
            ]);

        $batch = FoodBatch::where('production_plan_id', $this->planA->id)->firstOrFail();

        $this->actingAs($this->asistenA)
            ->post(route('production.batches.actual', $batch), [
                'actual_portions' => 350,
            ]);

        // Fake image (executable text file with .jpg extension)
        $fakeFile = UploadedFile::fake()->create('malicious.php.jpg', 100, 'application/x-php');

        $response = $this->actingAs($this->asistenA)
            ->post(route('production.batches.submit-qc', $batch), [
                'photo' => $fakeFile,
            ]);

        $response->assertSessionHasErrors('photo');
        $this->assertEquals(FoodBatchStatus::PREPARING, $batch->fresh()->status);
    }

    /**
     * 4. Test: File exceeding 5MB size limit is rejected.
     */
    public function test_file_too_large_rejected(): void
    {
        Storage::fake('local');

        $this->actingAs($this->asistenA)
            ->post(route('production.plans.batches.store', $this->planA), [
                'planned_portions' => 350,
            ]);

        $batch = FoodBatch::where('production_plan_id', $this->planA->id)->firstOrFail();

        $this->actingAs($this->asistenA)
            ->post(route('production.batches.actual', $batch), [
                'actual_portions' => 350,
            ]);

        // File of 6 MB (> 5120 KB)
        $largeFile = UploadedFile::fake()->image('oversize.jpg')->size(6 * 1024);

        $response = $this->actingAs($this->asistenA)
            ->post(route('production.batches.submit-qc', $batch), [
                'photo' => $largeFile,
            ]);

        $response->assertSessionHasErrors('photo');
    }

    /**
     * 5. Test: Download private evidence photo across tenants is 403 Forbidden.
     */
    public function test_cross_tenant_evidence_download_forbidden_403(): void
    {
        Storage::fake('local');
        $photoFile = UploadedFile::fake()->image('food_ready.jpg');

        $this->actingAs($this->asistenA)
            ->post(route('production.plans.batches.store', $this->planA), [
                'planned_portions' => 350,
            ]);

        $batch = FoodBatch::where('production_plan_id', $this->planA->id)->firstOrFail();

        $this->actingAs($this->asistenA)
            ->post(route('production.batches.actual', $batch), [
                'actual_portions' => 350,
            ]);

        $this->actingAs($this->asistenA)
            ->post(route('production.batches.submit-qc', $batch), [
                'photo' => $photoFile,
            ]);

        $evidencePhoto = EvidencePhoto::where('evidenceable_id', $batch->id)->firstOrFail();

        // 1. Authorized Pengawas Gizi from same SPPG A can access
        $responseAuthorized = $this->actingAs($this->giziA)
            ->get(route('evidence-photos.show', $evidencePhoto));
        $responseAuthorized->assertOk();

        // 2. Unauthorized staff from different SPPG B gets 403 Forbidden
        $responseCross = $this->actingAs($this->giziB)
            ->get(route('evidence-photos.show', $evidencePhoto));
        $responseCross->assertForbidden();

        // 3. Unauthenticated guest gets redirected to login
        auth()->logout();
        $responseGuest = $this->get(route('evidence-photos.show', $evidencePhoto));
        $responseGuest->assertRedirect(route('login'));
    }

    /**
     * 6. Test: QC inspection without required evidence (photo or exception) is rejected.
     */
    public function test_qc_inspection_without_valid_evidence_rejected(): void
    {
        // Directly create batch in QC_CHECK without photo and without exception
        $batch = FoodBatch::create([
            'production_plan_id' => $this->planA->id,
            'menu_id' => $this->menuA->id,
            'batch_code' => 'BATCH-TEST-NO-EVID',
            'planned_portions' => 350,
            'actual_portions' => 350,
            'status' => FoodBatchStatus::QC_CHECK,
            'created_by' => $this->asistenA->id,
        ]);

        $this->assertFalse($batch->hasValidEvidence());

        // Gizi tries to approve without evidence
        $response = $this->actingAs($this->giziA)
            ->post(route('nutrition.qc.inspect', $batch), [
                'appearance_ok' => 1,
                'aroma_ok' => 1,
                'texture_ok' => 1,
                'taste_tested' => 1,
                'taste_ok' => 1,
                'accepted_portions' => 350,
                'temperature_c' => 65.0,
                'sop_reference' => 'SOP-BGN-MUTU-01',
                'decision_reason' => 'Makanan tampak baik tapi tanpa bukti.',
                'conclusion' => QualityCheckConclusion::SAFE->value,
            ]);

        $response->assertSessionHasErrors('evidence');
        $this->assertEquals(FoodBatchStatus::QC_CHECK, $batch->fresh()->status);
    }

    /**
     * 7. Test: QC inspection missing mandatory organoleptic parameters is rejected.
     */
    public function test_qc_inspection_missing_mandatory_parameters_rejected(): void
    {
        $batch = FoodBatch::create([
            'production_plan_id' => $this->planA->id,
            'menu_id' => $this->menuA->id,
            'batch_code' => 'BATCH-TEST-MISSING-PARAM',
            'planned_portions' => 350,
            'actual_portions' => 350,
            'has_evidence_exception' => true,
            'evidence_exception_reason' => 'Kamera mati saat penyiapan batch di dapur.',
            'evidence_exception_status' => 'APPROVED',
            'evidence_exception_approved_by' => $this->kepalaA->id,
            'status' => FoodBatchStatus::QC_CHECK,
            'created_by' => $this->asistenA->id,
        ]);

        // Missing aroma_ok and decision_reason
        $response = $this->actingAs($this->giziA)
            ->post(route('nutrition.qc.inspect', $batch), [
                'appearance_ok' => 1,
                'texture_ok' => 1,
                'taste_tested' => 1,
                'taste_ok' => 1,
                'accepted_portions' => 350,
                'conclusion' => QualityCheckConclusion::SAFE->value,
            ]);

        $response->assertSessionHasErrors(['aroma_ok', 'decision_reason']);
    }

    /**
     * 8. Test: QC decision does not rely on temperature alone (>60C with defective organoleptic cannot be SAFE).
     */
    public function test_qc_decision_does_not_rely_on_temperature_alone(): void
    {
        $batch = FoodBatch::create([
            'production_plan_id' => $this->planA->id,
            'menu_id' => $this->menuA->id,
            'batch_code' => 'BATCH-TEST-TEMP-ONLY',
            'planned_portions' => 350,
            'actual_portions' => 350,
            'has_evidence_exception' => true,
            'evidence_exception_reason' => 'Kamera mati saat penyiapan batch di dapur.',
            'evidence_exception_status' => 'APPROVED',
            'evidence_exception_approved_by' => $this->kepalaA->id,
            'status' => FoodBatchStatus::QC_CHECK,
            'created_by' => $this->asistenA->id,
        ]);

        // High temperature (75C) but aroma is defective (aroma_ok = 0)
        $response = $this->actingAs($this->giziA)
            ->post(route('nutrition.qc.inspect', $batch), [
                'appearance_ok' => 1,
                'aroma_ok' => 0, // Defective aroma!
                'texture_ok' => 1,
                'taste_tested' => 1,
                'taste_ok' => 1,
                'accepted_portions' => 350,
                'temperature_c' => 75.0, // High temperature
                'sop_reference' => 'SOP-BGN-MUTU-01',
                'decision_reason' => 'Suhu panas tapi aroma menyengat tidak wajar.',
                'conclusion' => QualityCheckConclusion::SAFE->value,
            ]);

        // Must reject SAFE conclusion because sensory parameter failed!
        $response->assertSessionHasErrors('conclusion');
        $this->assertNotEquals(FoodBatchStatus::RELEASED, $batch->fresh()->status);
    }

    /**
     * 9. Test: Actual portions differing from planned portions logs discrepancy.
     */
    public function test_actual_portions_different_from_target_logs_discrepancy(): void
    {
        $this->actingAs($this->asistenA)
            ->post(route('production.plans.batches.store', $this->planA), [
                'planned_portions' => 350,
            ]);

        $batch = FoodBatch::where('production_plan_id', $this->planA->id)->firstOrFail();

        $this->actingAs($this->asistenA)
            ->post(route('production.batches.actual', $batch), [
                'actual_portions' => 340, // 10 portions shortage
                'notes' => 'Bahan ayam menyusut saat pemotongan',
            ]);

        $batch->refresh();
        $this->assertEquals(340, $batch->actual_portions);
        $this->assertEquals(-10, $batch->discrepancy());

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'food_batch.actual_portions_recorded',
            'auditable_id' => $batch->id,
        ]);
    }

    /**
     * 10. Test: Cumulative planned portions across batches cannot exceed plan allocations (600 portions).
     */
    public function test_total_planned_batch_portions_cannot_exceed_plan_allocations(): void
    {
        // Total allocated on planA is 600 portions (350 + 250)
        // Batch 1: 350 portions
        $this->actingAs($this->asistenA)
            ->post(route('production.plans.batches.store', $this->planA), [
                'planned_portions' => 350,
            ]);

        // Batch 2: 250 portions -> total is now 600
        $this->actingAs($this->asistenA)
            ->post(route('production.plans.batches.store', $this->planA), [
                'planned_portions' => 250,
            ]);

        // Batch 3: 50 portions -> total would be 650 > 600! Must be rejected!
        $response = $this->actingAs($this->asistenA)
            ->post(route('production.plans.batches.store', $this->planA), [
                'planned_portions' => 50,
            ]);

        $response->assertSessionHasErrors('planned_portions');
        $this->assertEquals(2, FoodBatch::where('production_plan_id', $this->planA->id)->count());
    }

    /**
     * 11. Test: Accepted portions cannot exceed actual portions.
     */
    public function test_accepted_portions_cannot_exceed_actual_portions(): void
    {
        $batch = FoodBatch::create([
            'production_plan_id' => $this->planA->id,
            'menu_id' => $this->menuA->id,
            'batch_code' => 'BATCH-TEST-EXCEED-ACTUAL',
            'planned_portions' => 350,
            'actual_portions' => 340,
            'has_evidence_exception' => true,
            'evidence_exception_reason' => 'Kamera mati saat penyiapan batch di dapur.',
            'evidence_exception_status' => 'APPROVED',
            'evidence_exception_approved_by' => $this->kepalaA->id,
            'status' => FoodBatchStatus::QC_CHECK,
            'created_by' => $this->asistenA->id,
        ]);

        // Attempting to accept 350 portions when actual is only 340
        $response = $this->actingAs($this->giziA)
            ->post(route('nutrition.qc.inspect', $batch), [
                'appearance_ok' => 1,
                'aroma_ok' => 1,
                'texture_ok' => 1,
                'taste_tested' => 1,
                'taste_ok' => 1,
                'accepted_portions' => 350, // > 340 actual!
                'sop_reference' => 'SOP-BGN-MUTU-01',
                'decision_reason' => 'Mencoba menerima lebih banyak dari hasil masak.',
                'conclusion' => QualityCheckConclusion::SAFE->value,
            ]);

        $response->assertSessionHasErrors('accepted_portions');
    }

    /**
     * 12. Test: Partial batch release reconciliation (accepted < actual portions).
     */
    public function test_partial_batch_release_reconciliation(): void
    {
        $batch = FoodBatch::create([
            'production_plan_id' => $this->planA->id,
            'menu_id' => $this->menuA->id,
            'batch_code' => 'BATCH-TEST-PARTIAL',
            'planned_portions' => 350,
            'actual_portions' => 340,
            'has_evidence_exception' => true,
            'evidence_exception_reason' => 'Kamera mati saat penyiapan batch di dapur.',
            'evidence_exception_status' => 'APPROVED',
            'evidence_exception_approved_by' => $this->kepalaA->id,
            'status' => FoodBatchStatus::QC_CHECK,
            'created_by' => $this->asistenA->id,
        ]);

        // Pengawas Gizi accepts 330 portions, rejecting 10 portions due to damaged packaging
        $response = $this->actingAs($this->giziA)
            ->post(route('nutrition.qc.inspect', $batch), [
                'appearance_ok' => 1,
                'aroma_ok' => 1,
                'texture_ok' => 1,
                'taste_tested' => 1,
                'taste_ok' => 1,
                'accepted_portions' => 330,
                'sop_reference' => 'SOP-BGN-MUTU-01',
                'decision_reason' => '330 porsi lolos prima, 10 porsi kemasan rusak disisihkan.',
                'conclusion' => QualityCheckConclusion::SAFE->value,
            ]);

        $response->assertRedirect(route('nutrition.qc.index'));

        $batch->refresh();
        $this->assertEquals(FoodBatchStatus::RELEASED, $batch->status);
        $this->assertEquals(330, $batch->accepted_portions);

        $qc = $batch->latestQualityCheck();
        $this->assertEquals(330, $qc->accepted_portions);
        $this->assertEquals(10, $qc->rejected_portions);
    }

    /**
     * 13. Test: Unreleased batches keep plan from being prematurely marked ready.
     */
    public function test_shortage_portions_does_not_prematurely_mark_plan_ready(): void
    {
        // Batch 1: 350 portions
        $this->actingAs($this->asistenA)
            ->post(route('production.plans.batches.store', $this->planA), [
                'planned_portions' => 350,
            ]);

        // Batch 2: 250 portions
        $this->actingAs($this->asistenA)
            ->post(route('production.plans.batches.store', $this->planA), [
                'planned_portions' => 250,
            ]);

        $batch1 = FoodBatch::where('production_plan_id', $this->planA->id)->firstOrFail();
        $batch2 = FoodBatch::where('production_plan_id', $this->planA->id)->skip(1)->firstOrFail();

        // Release batch 1 only
        $batch1->update([
            'actual_portions' => 350,
            'accepted_portions' => 350,
            'status' => FoodBatchStatus::RELEASED,
            'released_by' => $this->giziA->id,
            'released_at' => now(),
            'has_evidence_exception' => true,
            'evidence_exception_reason' => 'Pengecualian sah untuk pengujian.',
            'evidence_exception_status' => 'APPROVED',
            'evidence_exception_approved_by' => $this->kepalaA->id,
        ]);

        // Batch 2 is still PREPARING! Plan must NOT be READY_FOR_DISTRIBUTION
        $this->assertNotEquals(ProductionPlanStatus::READY_FOR_DISTRIBUTION, $this->planA->fresh()->status);
    }

    /**
     * 14. Test: Double-submitting or inspecting already released batch is rejected.
     */
    public function test_double_submit_qc_is_protected(): void
    {
        $batch = FoodBatch::create([
            'production_plan_id' => $this->planA->id,
            'menu_id' => $this->menuA->id,
            'batch_code' => 'BATCH-TEST-RELEASED',
            'planned_portions' => 350,
            'actual_portions' => 350,
            'accepted_portions' => 350,
            'status' => FoodBatchStatus::RELEASED,
            'released_by' => $this->giziA->id,
            'released_at' => now(),
            'created_by' => $this->asistenA->id,
        ]);

        // Trying to inspect already released batch
        $response = $this->actingAs($this->giziA)
            ->post(route('nutrition.qc.inspect', $batch), [
                'appearance_ok' => 1,
                'aroma_ok' => 1,
                'texture_ok' => 1,
                'taste_tested' => 1,
                'taste_ok' => 1,
                'accepted_portions' => 350,
                'decision_reason' => 'Mencoba inspeksi ulang batch yang sudah dilepas.',
                'conclusion' => QualityCheckConclusion::SAFE->value,
            ]);

        $response->assertSessionHasErrors('status');
    }

    /**
     * 15. Test: Re-inspection after ON_HOLD creates append-only history.
     */
    public function test_reinspection_after_on_hold_creates_append_only_history(): void
    {
        $batch = FoodBatch::create([
            'production_plan_id' => $this->planA->id,
            'menu_id' => $this->menuA->id,
            'batch_code' => 'BATCH-TEST-REINSPECT',
            'planned_portions' => 350,
            'actual_portions' => 350,
            'has_evidence_exception' => true,
            'evidence_exception_reason' => 'Pengecualian bukti sah di dapur.',
            'evidence_exception_status' => 'APPROVED',
            'evidence_exception_approved_by' => $this->kepalaA->id,
            'status' => FoodBatchStatus::QC_CHECK,
            'created_by' => $this->asistenA->id,
        ]);

        // First check: ON_HOLD
        $this->actingAs($this->giziA)
            ->post(route('nutrition.qc.inspect', $batch), [
                'appearance_ok' => 1,
                'aroma_ok' => 0,
                'texture_ok' => 1,
                'taste_tested' => 0,
                'taste_skipped_reason' => 'Aroma mencurigakan saat pertama kali diperiksa.',
                'decision_reason' => 'Batch ditahan untuk menunggu evaluasi ulang kru dapur.',
                'conclusion' => QualityCheckConclusion::HOLD->value,
            ]);

        $batch->refresh();
        $this->assertEquals(FoodBatchStatus::ON_HOLD, $batch->status);
        $this->assertEquals(1, $batch->qualityChecks()->count());

        // Second check (Re-inspection after seasoning adjustment): SAFE
        $this->actingAs($this->giziA)
            ->post(route('nutrition.qc.inspect', $batch), [
                'appearance_ok' => 1,
                'aroma_ok' => 1,
                'texture_ok' => 1,
                'taste_tested' => 1,
                'taste_ok' => 1,
                'accepted_portions' => 350,
                'sop_reference' => 'SOP-BGN-MUTU-01',
                'decision_reason' => 'Koreksi resep selesai, aroma segar dan uji rasa lulus.',
                'conclusion' => QualityCheckConclusion::SAFE->value,
            ]);

        $batch->refresh();
        $this->assertEquals(FoodBatchStatus::RELEASED, $batch->status);

        // Crucial: Must have 2 quality check records in history (append-only, not overwritten!)
        $this->assertEquals(2, $batch->qualityChecks()->count());
        $this->assertEquals(QualityCheckConclusion::HOLD, $batch->qualityChecks()->orderBy('id', 'asc')->first()->conclusion);
        $this->assertEquals(QualityCheckConclusion::SAFE, $batch->qualityChecks()->orderBy('id', 'desc')->first()->conclusion);
    }

    /**
     * 16. Test: Editing released batch (advancing stage or modifying actual portions) is strictly rejected.
     */
    public function test_editing_released_batch_is_strictly_rejected(): void
    {
        $batch = FoodBatch::create([
            'production_plan_id' => $this->planA->id,
            'menu_id' => $this->menuA->id,
            'batch_code' => 'BATCH-TEST-LOCKED',
            'planned_portions' => 350,
            'actual_portions' => 350,
            'accepted_portions' => 350,
            'status' => FoodBatchStatus::RELEASED,
            'released_by' => $this->giziA->id,
            'released_at' => now(),
            'created_by' => $this->asistenA->id,
        ]);

        // Attempting to advance stage
        $responseStage = $this->actingAs($this->asistenA)
            ->post(route('production.batches.stage', $batch), [
                'stage' => ProductionStage::COOKING->value,
            ]);
        $responseStage->assertSessionHasErrors('status');

        // Attempting to change actual portions
        $responseActual = $this->actingAs($this->asistenA)
            ->post(route('production.batches.actual', $batch), [
                'actual_portions' => 300,
            ]);
        $responseActual->assertSessionHasErrors('status');
    }

    /**
     * 17. Test: Cross-SPPG batch creation and inspection are forbidden (403).
     */
    public function test_cross_sppg_batch_creation_and_inspection_forbidden_403(): void
    {
        // Asisten B tries to create batch on Plan A
        $responseCreate = $this->actingAs($this->asistenB)
            ->post(route('production.plans.batches.store', $this->planA), [
                'planned_portions' => 100,
            ]);
        $responseCreate->assertForbidden();

        $batchA = FoodBatch::create([
            'production_plan_id' => $this->planA->id,
            'menu_id' => $this->menuA->id,
            'batch_code' => 'BATCH-SPPG-A',
            'planned_portions' => 350,
            'actual_portions' => 350,
            'has_evidence_exception' => true,
            'evidence_exception_reason' => 'Pengecualian bukti sah di dapur.',
            'status' => FoodBatchStatus::QC_CHECK,
            'created_by' => $this->asistenA->id,
        ]);

        // Gizi B tries to inspect batch from SPPG A
        $responseInspect = $this->actingAs($this->giziB)
            ->post(route('nutrition.qc.inspect', $batchA), [
                'appearance_ok' => 1,
                'aroma_ok' => 1,
                'texture_ok' => 1,
                'taste_tested' => 1,
                'taste_ok' => 1,
                'decision_reason' => 'Inspeksi ilegal lintas SPPG.',
                'conclusion' => QualityCheckConclusion::SAFE->value,
            ]);
        $responseInspect->assertForbidden();
    }

    /**
     * 18. Test: PBI-07 Bidirectional Batch Traceability (Page C02 batches.show).
     */
    public function test_pbi_07_bidirectional_batch_traceability_page_c02(): void
    {
        // 1. Create batch via service (creates DeliveryItem automatically)
        $this->actingAs($this->asistenA)
            ->post(route('production.plans.batches.store', $this->planA), [
                'planned_portions' => 350,
            ]);

        $batch = FoodBatch::where('production_plan_id', $this->planA->id)->firstOrFail();

        // Verify DeliveryItems created connecting batch to School A1
        $this->assertDatabaseHas('delivery_items', [
            'food_batch_id' => $batch->id,
            'planned_portions' => 350,
            'status' => 'PENDING_DISPATCH',
        ]);

        // 2. Open Page C02 (/batches/{batch}) as Asisten Lapangan
        $responseAsisten = $this->actingAs($this->asistenA)
            ->get(route('batches.show', $batch));

        $responseAsisten->assertOk();
        $responseAsisten->assertSee($batch->batch_code);
        $responseAsisten->assertSee($this->menuA->name);
        $responseAsisten->assertSee('SCH-SDN-01');
        $responseAsisten->assertSee('Belum dijadwalkan untuk pengiriman');

        // 3. Open Page C02 as Pengawas Gizi
        $responseGizi = $this->actingAs($this->giziA)
            ->get(route('batches.show', $batch));
        $responseGizi->assertOk();
        $responseGizi->assertSee('Meja Inspeksi QC (G03)');

        // 4. Open Page C02 as Kepala SPPG
        $responseKepala = $this->actingAs($this->kepalaA)
            ->get(route('batches.show', $batch));
        $responseKepala->assertOk();

        // 5. Cross-SPPG staff (Asisten B) gets 403 Forbidden
        $responseCross = $this->actingAs($this->asistenB)
            ->get(route('batches.show', $batch));
        $responseCross->assertForbidden();

        // 6. Driver not assigned to this batch gets 403 Forbidden
        $responseDriver = $this->actingAs($this->driverA)
            ->get(route('batches.show', $batch));
        $responseDriver->assertForbidden();
    }

    /**
     * 19. Test: Bidirectional Traceability from School Allocation back to FoodBatch.
     */
    public function test_bidirectional_traceability_from_allocation_to_batch(): void
    {
        // Batch 1 covers 350 portions for School A1
        $this->actingAs($this->asistenA)
            ->post(route('production.plans.batches.store', $this->planA), [
                'planned_portions' => 350,
            ]);

        // Batch 2 covers 250 portions for School A2
        $this->actingAs($this->asistenA)
            ->post(route('production.plans.batches.store', $this->planA), [
                'planned_portions' => 250,
            ]);

        $batch1 = FoodBatch::where('production_plan_id', $this->planA->id)->firstOrFail();
        $batch2 = FoodBatch::where('production_plan_id', $this->planA->id)->skip(1)->firstOrFail();

        // Check School A1 allocation
        $alloc1 = ProductionAllocation::where('production_plan_id', $this->planA->id)
            ->where('school_id', $this->schoolA1->id)
            ->firstOrFail();

        // School A1 is traced back to Batch 1
        $itemsForSchool1 = DeliveryItem::where('production_allocation_id', $alloc1->id)->get();
        $this->assertCount(1, $itemsForSchool1);
        $this->assertEquals($batch1->id, $itemsForSchool1->first()->food_batch_id);

        // Check School A2 allocation
        $alloc2 = ProductionAllocation::where('production_plan_id', $this->planA->id)
            ->where('school_id', $this->schoolA2->id)
            ->firstOrFail();

        // School A2 is traced back to Batch 2
        $itemsForSchool2 = DeliveryItem::where('production_allocation_id', $alloc2->id)->get();
        $this->assertCount(1, $itemsForSchool2);
        $this->assertEquals($batch2->id, $itemsForSchool2->first()->food_batch_id);
    }

    /**
     * 20. Test: Plan readiness is BLOCKED if batches have shortage against total plan allocations.
     * (Critical Gate A: Shortage 446 portions prevents premature READY_FOR_DISTRIBUTION).
     */
    public function test_plan_readiness_blocked_if_batches_have_shortage_against_total_allocated(): void
    {
        $this->planA->update(['status' => ProductionPlanStatus::IN_PRODUCTION]);

        // Plan total allocations is 600 portions (School A1: 350, School A2: 250)
        // Batch 1: 350 planned, 340 actual, 330 accepted (10 rejected)
        $batch1 = FoodBatch::create([
            'production_plan_id' => $this->planA->id,
            'menu_id' => $this->menuA->id,
            'batch_code' => 'BATCH-SHORTAGE-01',
            'planned_portions' => 350,
            'actual_portions' => 340,
            'status' => FoodBatchStatus::QC_CHECK,
            'has_evidence_exception' => true,
            'evidence_exception_reason' => 'Pengecualian kamera operasional resmi.',
            'evidence_exception_status' => 'APPROVED',
            'evidence_exception_approved_by' => $this->kepalaA->id,
            'created_by' => $this->asistenA->id,
        ]);

        // Batch 2: 250 planned, 250 actual, 240 accepted (10 rejected)
        $batch2 = FoodBatch::create([
            'production_plan_id' => $this->planA->id,
            'menu_id' => $this->menuA->id,
            'batch_code' => 'BATCH-SHORTAGE-02',
            'planned_portions' => 250,
            'actual_portions' => 250,
            'status' => FoodBatchStatus::QC_CHECK,
            'has_evidence_exception' => true,
            'evidence_exception_reason' => 'Pengecualian kamera operasional resmi.',
            'evidence_exception_status' => 'APPROVED',
            'evidence_exception_approved_by' => $this->kepalaA->id,
            'created_by' => $this->asistenA->id,
        ]);

        // QC Release Batch 1 with 330 accepted
        app(QualityControlService::class)->recordQualityCheck($batch1, [
            'appearance_ok' => 1,
            'aroma_ok' => 1,
            'texture_ok' => 1,
            'taste_tested' => 1,
            'taste_ok' => 1,
            'accepted_portions' => 330,
            'decision_reason' => 'Batch 1 lolos 330 porsi, 10 porsi kemasan rusak.',
            'conclusion' => QualityCheckConclusion::SAFE->value,
        ], $this->giziA);

        // QC Release Batch 2 with 240 accepted
        app(QualityControlService::class)->recordQualityCheck($batch2, [
            'appearance_ok' => 1,
            'aroma_ok' => 1,
            'texture_ok' => 1,
            'taste_tested' => 1,
            'taste_ok' => 1,
            'accepted_portions' => 240,
            'decision_reason' => 'Batch 2 lolos 240 porsi, 10 porsi kemasan rusak.',
            'conclusion' => QualityCheckConclusion::SAFE->value,
        ], $this->giziA);

        // Total accepted is 330 + 240 = 570 < 600 total allocated (30 portions shortage!)
        // All batches are RELEASED, but the plan MUST NOT be marked READY_FOR_DISTRIBUTION!
        $this->assertEquals(FoodBatchStatus::RELEASED, $batch1->fresh()->status);
        $this->assertEquals(FoodBatchStatus::RELEASED, $batch2->fresh()->status);
        $this->assertNotEquals(ProductionPlanStatus::READY_FOR_DISTRIBUTION, $this->planA->fresh()->status);
        $this->assertEquals(ProductionPlanStatus::IN_PRODUCTION, $this->planA->fresh()->status);
    }

    /**
     * 21. Test: Plan readiness is BLOCKED if some target portions were never batched.
     */
    public function test_plan_readiness_blocked_if_target_portions_not_fully_batched(): void
    {
        $this->planA->update(['status' => ProductionPlanStatus::IN_PRODUCTION]);

        // Only 1 batch of 350 portions is created and released for a plan allocating 600 portions!
        $batch = FoodBatch::create([
            'production_plan_id' => $this->planA->id,
            'menu_id' => $this->menuA->id,
            'batch_code' => 'BATCH-PARTIAL-BATCHED',
            'planned_portions' => 350,
            'actual_portions' => 350,
            'status' => FoodBatchStatus::QC_CHECK,
            'has_evidence_exception' => true,
            'evidence_exception_reason' => 'Pengecualian kamera operasional resmi.',
            'evidence_exception_status' => 'APPROVED',
            'evidence_exception_approved_by' => $this->kepalaA->id,
            'created_by' => $this->asistenA->id,
        ]);

        app(QualityControlService::class)->recordQualityCheck($batch, [
            'appearance_ok' => 1,
            'aroma_ok' => 1,
            'texture_ok' => 1,
            'taste_tested' => 1,
            'taste_ok' => 1,
            'accepted_portions' => 350,
            'decision_reason' => 'Batch lolos seluruh 350 porsi dengan mutu prima.',
            'conclusion' => QualityCheckConclusion::SAFE->value,
        ], $this->giziA);

        $this->assertEquals(FoodBatchStatus::RELEASED, $batch->fresh()->status);
        // Even though 0 unreleased batches exist, only 350 portions were batched! Plan remains IN_PRODUCTION!
        $this->assertEquals(ProductionPlanStatus::IN_PRODUCTION, $this->planA->fresh()->status);
    }

    /**
     * 22. Test: Delivery items are reconciled with accepted portions on partial QC release.
     */
    public function test_delivery_items_reconciled_with_actual_accepted_portions(): void
    {
        $this->actingAs($this->asistenA)
            ->post(route('production.plans.batches.store', $this->planA), [
                'planned_portions' => 350,
            ]);

        $batch = FoodBatch::where('production_plan_id', $this->planA->id)->firstOrFail();
        $batch->update([
            'actual_portions' => 340,
            'status' => FoodBatchStatus::QC_CHECK,
            'has_evidence_exception' => true,
            'evidence_exception_reason' => 'Pengecualian kamera operasional resmi.',
            'evidence_exception_status' => 'APPROVED',
            'evidence_exception_approved_by' => $this->kepalaA->id,
        ]);

        // Pengawas Gizi accepts 330 portions
        app(QualityControlService::class)->recordQualityCheck($batch, [
            'appearance_ok' => 1,
            'aroma_ok' => 1,
            'texture_ok' => 1,
            'taste_tested' => 1,
            'taste_ok' => 1,
            'accepted_portions' => 330,
            'decision_reason' => '330 porsi lolos prima, 10 porsi ditolak.',
            'conclusion' => QualityCheckConclusion::SAFE->value,
        ], $this->giziA);

        $deliveryItem = $batch->deliveryItems()->firstOrFail();
        $this->assertEquals(350, $deliveryItem->planned_portions);
        $this->assertEquals(330, $deliveryItem->released_portions);
        $this->assertEquals('PENDING_DISPATCH', $deliveryItem->status);
    }

    /**
     * 23. Test: Evidence exception cannot be self-approved by the requester.
     */
    public function test_evidence_exception_cannot_be_self_approved_by_requester(): void
    {
        $batch = FoodBatch::create([
            'production_plan_id' => $this->planA->id,
            'menu_id' => $this->menuA->id,
            'batch_code' => 'BATCH-TEST-SELF-APPROVE',
            'planned_portions' => 350,
            'actual_portions' => 350,
            'has_evidence_exception' => true,
            'evidence_exception_reason' => 'Kamera mati saat penyiapan batch di dapur.',
            'evidence_exception_requested_by' => $this->asistenA->id,
            'evidence_exception_status' => 'PENDING',
            'status' => FoodBatchStatus::QC_CHECK,
            'created_by' => $this->asistenA->id,
        ]);

        // Asisten A tries to self-approve
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(FoodBatchService::class)->approveEvidenceException($batch, $this->asistenA);
    }

    /**
     * 24. Test: PIC Sekolah A cannot view photo of a batch dedicated to School B.
     */
    public function test_pic_sekolah_cannot_view_photo_of_batch_dedicated_to_different_school(): void
    {
        Storage::fake('local');
        $photoFile = UploadedFile::fake()->image('food_ready.jpg');

        $picA1 = User::firstOrCreate(
            ['email' => 'pic.sdn01@mbgtrack.test'],
            [
                'school_id' => $this->schoolA1->id,
                'name' => 'PIC SDN 01',
                'password' => bcrypt('password'),
                'role' => UserRole::PIC_SEKOLAH,
                'is_active' => true,
            ]
        );

        $picA2 = User::firstOrCreate(
            ['email' => 'pic.smpn02@mbgtrack.test'],
            [
                'school_id' => $this->schoolA2->id,
                'name' => 'PIC SMPN 02',
                'password' => bcrypt('password'),
                'role' => UserRole::PIC_SEKOLAH,
                'is_active' => true,
            ]
        );

        // Batch 1 dedicated to School A1 (350 portions)
        $this->actingAs($this->asistenA)
            ->post(route('production.plans.batches.store', $this->planA), [
                'planned_portions' => 350,
            ]);

        $batch1 = FoodBatch::where('production_plan_id', $this->planA->id)->firstOrFail();
        $batch1->update(['actual_portions' => 350]);

        $this->actingAs($this->asistenA)
            ->post(route('production.batches.submit-qc', $batch1), [
                'photo' => $photoFile,
            ]);

        $photo = $batch1->foodReadyPhoto();
        $this->assertNotNull($photo);

        // PIC of School A1 CAN view
        $responsePic1 = $this->actingAs($picA1)
            ->get(route('evidence-photos.show', $photo));
        $responsePic1->assertOk();

        // PIC of School A2 CANNOT view because Batch 1 is dedicated to School A1!
        $responsePic2 = $this->actingAs($picA2)
            ->get(route('evidence-photos.show', $photo));
        $responsePic2->assertForbidden();
    }
}
