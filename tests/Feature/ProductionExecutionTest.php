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
use App\Models\FoodBatch;
use App\Models\Menu;
use App\Models\ProductionAllocation;
use App\Models\ProductionPlan;
use App\Models\School;
use App\Models\Sppg;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\MbgTrackBaselineSeeder;
use Database\Seeders\MenuBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionExecutionTest extends TestCase
{
    use RefreshDatabase;

    protected Sppg $sppg;
    protected School $schoolA;
    protected School $schoolB;
    protected School $schoolC;
    protected Menu $activeMenu;
    protected User $asisten;
    protected User $gizi;
    protected User $kepala;
    protected ProductionPlan $finalizedPlan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MbgTrackBaselineSeeder::class);
        $this->seed(MenuBaselineSeeder::class);

        $this->sppg = Sppg::where('code', 'SPPG-SUB-01')->firstOrFail();
        $this->schoolA = School::where('school_code', 'SCH-SDN-01')->firstOrFail();
        $this->schoolB = School::where('school_code', 'SCH-SMPN-02')->firstOrFail();
        $this->schoolC = School::where('school_code', 'SCH-SMAN-05')->firstOrFail();

        $this->asisten = User::where('email', 'asisten@mbgtrack.test')->firstOrFail();
        $this->gizi = User::where('email', 'gizi@mbgtrack.test')->firstOrFail();
        $this->kepala = User::where('email', 'kepala@mbgtrack.test')->firstOrFail();

        $this->activeMenu = Menu::where('sppg_id', $this->sppg->id)
            ->where('status', MenuStatus::ACTIVE)
            ->firstOrFail();

        // Create a finalized production plan for tomorrow with allocations
        $date = Carbon::tomorrow();

        $reqA = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'sppg_id' => $this->sppg->id,
            'service_date' => $date,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
            'created_by' => $this->kepala->id,
            'confirmed_at' => now(),
        ]);

        $reqB = DailyPortionRequest::create([
            'school_id' => $this->schoolB->id,
            'sppg_id' => $this->sppg->id,
            'service_date' => $date,
            'registered_count' => 276,
            'requested_portions' => 276,
            'status' => PortionRequestStatus::CONFIRMED,
            'created_by' => $this->kepala->id,
            'confirmed_at' => now(),
        ]);

        $reqC = DailyPortionRequest::create([
            'school_id' => $this->schoolC->id,
            'sppg_id' => $this->sppg->id,
            'service_date' => $date,
            'registered_count' => 410,
            'requested_portions' => 410,
            'status' => PortionRequestStatus::CONFIRMED,
            'created_by' => $this->kepala->id,
            'confirmed_at' => now(),
        ]);

        $this->finalizedPlan = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'service_date' => $date,
            'menu_id' => $this->activeMenu->id,
            'status' => ProductionPlanStatus::FINALIZED,
            'created_by' => $this->kepala->id,
            'finalized_by' => $this->kepala->id,
            'finalized_at' => now(),
            'notes' => 'Rencana produksi resmi teruji',
        ]);

        ProductionAllocation::create([
            'production_plan_id' => $this->finalizedPlan->id,
            'portion_request_id' => $reqA->id,
            'school_id' => $this->schoolA->id,
            'allocated_portions' => 340,
        ]);
        ProductionAllocation::create([
            'production_plan_id' => $this->finalizedPlan->id,
            'portion_request_id' => $reqB->id,
            'school_id' => $this->schoolB->id,
            'allocated_portions' => 276,
        ]);
        ProductionAllocation::create([
            'production_plan_id' => $this->finalizedPlan->id,
            'portion_request_id' => $reqC->id,
            'school_id' => $this->schoolC->id,
            'allocated_portions' => 410,
        ]);
    }

    public function test_asisten_can_view_execution_workspace_for_finalized_plan(): void
    {
        $response = $this->actingAs($this->asisten)
            ->get(route('production.plans.execute', $this->finalizedPlan));

        $response->assertOk();
        $response->assertViewIs('production.execution.index');
        $response->assertSee('Eksekusi Produksi &amp; Pelacakan Batch Dapur', false);
        $response->assertSee('1,026'); // Total planned portions (340 + 276 + 410)
    }

    public function test_cross_sppg_user_cannot_access_execution_workspace(): void
    {
        $otherSppg = Sppg::create([
            'code' => 'SPPG-MLG-99',
            'name' => 'SPPG Malang Luar',
            'city' => 'Malang',
            'address' => 'Jl. Ijen No. 1, Malang',
            'max_daily_portions' => 1000,
            'is_active' => true,
        ]);

        $otherAsisten = User::create([
            'name' => 'Asisten Luar',
            'email' => 'asisten.luar@mbgtrack.test',
            'password' => bcrypt('password'),
            'role' => UserRole::ASISTEN_LAPANGAN,
            'sppg_id' => $otherSppg->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($otherAsisten)
            ->get(route('production.plans.execute', $this->finalizedPlan));

        $response->assertForbidden();
    }

    public function test_asisten_can_create_food_batch_and_plan_transitions_to_in_production(): void
    {
        $this->assertEquals(ProductionPlanStatus::FINALIZED, $this->finalizedPlan->status);

        $response = $this->actingAs($this->asisten)
            ->post(route('production.plans.batches.store', $this->finalizedPlan), [
                'planned_portions' => 500,
                'portion_category' => 'STANDARD',
                'notes' => 'Batch kloter 1 pagi',
            ]);

        $response->assertRedirect(route('production.plans.execute', $this->finalizedPlan));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('food_batches', [
            'production_plan_id' => $this->finalizedPlan->id,
            'planned_portions' => 500,
            'status' => FoodBatchStatus::PREPARING->value,
            'created_by' => $this->asisten->id,
        ]);

        $batch = FoodBatch::where('production_plan_id', $this->finalizedPlan->id)->first();
        $this->assertNotNull($batch);
        $this->assertStringStartsWith('BATCH-', $batch->batch_code);

        // Stage log must be created for PREPARATION
        $this->assertDatabaseHas('production_stage_logs', [
            'food_batch_id' => $batch->id,
            'stage' => ProductionStage::PREPARATION->value,
            'updated_by' => $this->asisten->id,
        ]);

        // Plan status must have transitioned to IN_PRODUCTION
        $this->assertEquals(ProductionPlanStatus::IN_PRODUCTION, $this->finalizedPlan->fresh()->status);
    }

    public function test_non_asisten_cannot_create_batch(): void
    {
        $response = $this->actingAs($this->gizi)
            ->post(route('production.plans.batches.store', $this->finalizedPlan), [
                'planned_portions' => 500,
            ]);

        $response->assertForbidden();

        $responseKepala = $this->actingAs($this->kepala)
            ->post(route('production.plans.batches.store', $this->finalizedPlan), [
                'planned_portions' => 500,
            ]);

        $responseKepala->assertForbidden();
    }

    public function test_target_portions_must_be_positive(): void
    {
        $response = $this->actingAs($this->asisten)
            ->post(route('production.plans.batches.store', $this->finalizedPlan), [
                'planned_portions' => 0,
            ]);

        $response->assertSessionHasErrors('planned_portions');

        $responseNegative = $this->actingAs($this->asisten)
            ->post(route('production.plans.batches.store', $this->finalizedPlan), [
                'planned_portions' => -10,
            ]);

        $responseNegative->assertSessionHasErrors('planned_portions');
    }

    public function test_asisten_can_advance_stage_in_strict_sequence(): void
    {
        // 1. Create batch (Starts at PREPARATION)
        $this->actingAs($this->asisten)
            ->post(route('production.plans.batches.store', $this->finalizedPlan), [
                'planned_portions' => 400,
            ]);

        $batch = FoodBatch::where('production_plan_id', $this->finalizedPlan->id)->firstOrFail();
        $this->assertEquals(FoodBatchStatus::PREPARING, $batch->status);

        // 2. Advance to COOKING
        $response1 = $this->actingAs($this->asisten)
            ->post(route('production.batches.stage', $batch), [
                'stage' => ProductionStage::COOKING->value,
                'notes' => 'Mulai penggorengan',
            ]);

        $response1->assertRedirect();
        $batch->refresh();
        $this->assertEquals(FoodBatchStatus::COOKING, $batch->status);

        // 3. Advance to PORTIONING
        $response2 = $this->actingAs($this->asisten)
            ->post(route('production.batches.stage', $batch), [
                'stage' => ProductionStage::PORTIONING->value,
                'notes' => 'Mulai penimbangan ompreng',
            ]);

        $response2->assertRedirect();
        $batch->refresh();
        $this->assertEquals(FoodBatchStatus::PORTIONING, $batch->status);

        // 4. Advance to PACKING
        $response3 = $this->actingAs($this->asisten)
            ->post(route('production.batches.stage', $batch), [
                'stage' => ProductionStage::PACKING->value,
                'notes' => 'Pengemasan wadah steril',
            ]);

        $response3->assertRedirect();
        $batch->refresh();
        $this->assertEquals(FoodBatchStatus::PACKED, $batch->status);
    }

    public function test_advancing_stage_out_of_order_is_rejected(): void
    {
        // Batch starts at PREPARATION
        $this->actingAs($this->asisten)
            ->post(route('production.plans.batches.store', $this->finalizedPlan), [
                'planned_portions' => 400,
            ]);

        $batch = FoodBatch::where('production_plan_id', $this->finalizedPlan->id)->firstOrFail();

        // Illegal jump: PREPARATION directly to PACKING
        $response = $this->actingAs($this->asisten)
            ->post(route('production.batches.stage', $batch), [
                'stage' => ProductionStage::PACKING->value,
            ]);

        $response->assertSessionHasErrors('stage');
        $this->assertEquals(FoodBatchStatus::PREPARING, $batch->fresh()->status);
    }

    public function test_asisten_can_record_actual_portions_and_negative_portions_is_rejected(): void
    {
        $this->actingAs($this->asisten)
            ->post(route('production.plans.batches.store', $this->finalizedPlan), [
                'planned_portions' => 400,
            ]);

        $batch = FoodBatch::where('production_plan_id', $this->finalizedPlan->id)->firstOrFail();

        // Negative actual portions rejected
        $responseNegative = $this->actingAs($this->asisten)
            ->post(route('production.batches.actual', $batch), [
                'actual_portions' => -5,
            ]);
        $responseNegative->assertSessionHasErrors('actual_portions');

        // Valid actual portions recorded
        $response = $this->actingAs($this->asisten)
            ->post(route('production.batches.actual', $batch), [
                'actual_portions' => 395,
                'notes' => '5 porsi susut saat penggorengan',
            ]);

        $response->assertRedirect();
        $batch->refresh();
        $this->assertEquals(395, $batch->actual_portions);
        // Original planned target is unmutated!
        $this->assertEquals(400, $batch->planned_portions);
    }

    public function test_asisten_can_submit_batch_to_qc_with_photo(): void
    {
        Storage::fake('public');

        $this->actingAs($this->asisten)
            ->post(route('production.plans.batches.store', $this->finalizedPlan), [
                'planned_portions' => 400,
            ]);

        $batch = FoodBatch::where('production_plan_id', $this->finalizedPlan->id)->firstOrFail();

        // Record actual portions
        $this->actingAs($this->asisten)
            ->post(route('production.batches.actual', $batch), [
                'actual_portions' => 400,
            ]);

        $photo = UploadedFile::fake()->image('food_ready.jpg');

        $response = $this->actingAs($this->asisten)
            ->post(route('production.batches.submit-qc', $batch), [
                'photo' => $photo,
                'notes' => 'Siap diperiksa gizi',
            ]);

        $response->assertRedirect();
        $batch->refresh();

        $this->assertEquals(FoodBatchStatus::QC_CHECK, $batch->status);
        $this->assertDatabaseHas('evidence_photos', [
            'evidenceable_type' => FoodBatch::class,
            'evidenceable_id' => $batch->id,
            'evidence_type' => 'FOOD_READY',
            'uploaded_by' => $this->asisten->id,
        ]);
    }

    public function test_cannot_submit_to_qc_without_recording_actual_portions(): void
    {
        $this->actingAs($this->asisten)
            ->post(route('production.plans.batches.store', $this->finalizedPlan), [
                'planned_portions' => 400,
            ]);

        $batch = FoodBatch::where('production_plan_id', $this->finalizedPlan->id)->firstOrFail();
        $this->assertNull($batch->actual_portions);

        $response = $this->actingAs($this->asisten)
            ->post(route('production.batches.submit-qc', $batch), [
                'has_evidence_exception' => 1,
                'evidence_exception_reason' => 'Kamera dapur bermasalah saat penyerahan batch.',
                'notes' => 'Langsung ke QC tanpa hitung',
            ]);

        $response->assertSessionHasErrors('actual_portions');
    }

    public function test_pengawas_gizi_can_inspect_batch_and_approve_as_safe(): void
    {
        Storage::fake('local');
        $photo = UploadedFile::fake()->image('food_ready.jpg');

        $this->actingAs($this->asisten)
            ->post(route('production.plans.batches.store', $this->finalizedPlan), [
                'planned_portions' => 1026,
            ]);

        $batch = FoodBatch::where('production_plan_id', $this->finalizedPlan->id)->firstOrFail();

        $this->actingAs($this->asisten)
            ->post(route('production.batches.actual', $batch), [
                'actual_portions' => 1026,
            ]);

        $this->actingAs($this->asisten)
            ->post(route('production.batches.submit-qc', $batch), [
                'photo' => $photo,
            ]);

        $batch->refresh();
        $this->assertEquals(FoodBatchStatus::QC_CHECK, $batch->status);

        // Gizi inspects batch and marks SAFE
        $response = $this->actingAs($this->gizi)
            ->post(route('nutrition.qc.inspect', $batch), [
                'appearance_ok' => 1,
                'aroma_ok' => 1,
                'texture_ok' => 1,
                'taste_tested' => 1,
                'taste_ok' => 1,
                'accepted_portions' => 350,
                'temperature_c' => 68.5,
                'sop_reference' => 'SOP-BGN-MUTU-01',
                'decision_reason' => 'Makanan matang sempurna, organoleptik segar, suhu di atas 60C.',
                'conclusion' => QualityCheckConclusion::SAFE->value,
                'notes' => 'Aroma segar, rasa gurih pas, suhu aman 68.5C.',
            ]);

        $response->assertRedirect(route('nutrition.qc.index'));
        $batch->refresh();

        $this->assertEquals(FoodBatchStatus::RELEASED, $batch->status);
        $this->assertEquals($this->gizi->id, $batch->released_by);
        $this->assertNotNull($batch->released_at);

        // Plan status remains IN_PRODUCTION because only 350 portions were produced and released out of 1026 allocated!
        $this->assertEquals(ProductionPlanStatus::IN_PRODUCTION, $this->finalizedPlan->fresh()->status);
    }

    public function test_pengawas_gizi_can_put_batch_on_hold_blocking_distribution(): void
    {
        Storage::fake('local');
        $photo = UploadedFile::fake()->image('food_ready.jpg');

        $this->actingAs($this->asisten)
            ->post(route('production.plans.batches.store', $this->finalizedPlan), [
                'planned_portions' => 1026,
            ]);

        $batch = FoodBatch::where('production_plan_id', $this->finalizedPlan->id)->firstOrFail();

        $this->actingAs($this->asisten)
            ->post(route('production.batches.actual', $batch), [
                'actual_portions' => 1026,
            ]);

        $this->actingAs($this->asisten)
            ->post(route('production.batches.submit-qc', $batch), [
                'photo' => $photo,
            ]);

        // Gizi inspects batch and marks ON_HOLD due to sour aroma
        $response = $this->actingAs($this->gizi)
            ->post(route('nutrition.qc.inspect', $batch), [
                'appearance_ok' => 1,
                'aroma_ok' => 0,
                'texture_ok' => 1,
                'taste_tested' => 0,
                'taste_skipped_reason' => 'Aroma berbau asam menyengat, tidak aman untuk dicicipi pemeriksa.',
                'temperature_c' => 45.0,
                'sop_reference' => 'SOP-BGN-MUTU-01',
                'decision_reason' => 'Aroma sayur terindikasi asam dan suhu turun ke 45C sehingga diblokir.',
                'conclusion' => QualityCheckConclusion::HOLD->value,
                'notes' => 'Aroma sayur terindikasi asam dan suhu turun ke 45C. Dilarang kirim.',
            ]);

        $response->assertRedirect(route('nutrition.qc.index'));
        $batch->refresh();

        $this->assertEquals(FoodBatchStatus::ON_HOLD, $batch->status);
        $this->assertNull($batch->released_by);
        $this->assertNull($batch->released_at);

        // Plan must NOT transition to READY_FOR_DISTRIBUTION
        $this->assertNotEquals(ProductionPlanStatus::READY_FOR_DISTRIBUTION, $this->finalizedPlan->fresh()->status);
    }

    public function test_non_gizi_cannot_perform_qc(): void
    {
        $this->actingAs($this->asisten)
            ->post(route('production.plans.batches.store', $this->finalizedPlan), [
                'planned_portions' => 1026,
            ]);

        $batch = FoodBatch::where('production_plan_id', $this->finalizedPlan->id)->firstOrFail();

        $this->actingAs($this->asisten)
            ->post(route('production.batches.actual', $batch), [
                'actual_portions' => 1026,
            ]);

        $this->actingAs($this->asisten)
            ->post(route('production.batches.submit-qc', $batch));

        // Asisten tries to QC own batch
        $responseAsisten = $this->actingAs($this->asisten)
            ->post(route('nutrition.qc.inspect', $batch), [
                'appearance_ok' => 1,
                'aroma_ok' => 1,
                'texture_ok' => 1,
                'taste_ok' => 1,
                'conclusion' => QualityCheckConclusion::SAFE->value,
            ]);

        $responseAsisten->assertForbidden();

        // Kepala SPPG tries to QC batch
        $responseKepala = $this->actingAs($this->kepala)
            ->post(route('nutrition.qc.inspect', $batch), [
                'appearance_ok' => 1,
                'aroma_ok' => 1,
                'texture_ok' => 1,
                'taste_ok' => 1,
                'conclusion' => QualityCheckConclusion::SAFE->value,
            ]);

        $responseKepala->assertForbidden();
    }

    public function test_qc_requires_all_organoleptic_checklist_parameters(): void
    {
        $this->actingAs($this->asisten)
            ->post(route('production.plans.batches.store', $this->finalizedPlan), [
                'planned_portions' => 1026,
            ]);

        $batch = FoodBatch::where('production_plan_id', $this->finalizedPlan->id)->firstOrFail();

        $this->actingAs($this->asisten)
            ->post(route('production.batches.actual', $batch), [
                'actual_portions' => 1026,
            ]);

        $this->actingAs($this->asisten)
            ->post(route('production.batches.submit-qc', $batch), [
                'has_evidence_exception' => 1,
                'evidence_exception_reason' => 'Kamera ponsel staf habis baterai di dapur.',
            ]);

        // Missing texture_ok and conclusion
        $response = $this->actingAs($this->gizi)
            ->post(route('nutrition.qc.inspect', $batch), [
                'appearance_ok' => 1,
                'aroma_ok' => 1,
                'taste_tested' => 1,
                'taste_ok' => 1,
                'decision_reason' => 'Pemeriksaan sampel awal.',
            ]);

        $response->assertSessionHasErrors(['texture_ok', 'conclusion']);
    }

    public function test_activity_logs_recorded_for_production_execution_lifecycle(): void
    {
        Storage::fake('local');
        $photo = UploadedFile::fake()->image('food_ready.jpg');

        $this->actingAs($this->asisten)
            ->post(route('production.plans.batches.store', $this->finalizedPlan), [
                'planned_portions' => 1026,
            ]);

        $batch = FoodBatch::where('production_plan_id', $this->finalizedPlan->id)->firstOrFail();

        $this->actingAs($this->asisten)
            ->post(route('production.batches.stage', $batch), [
                'stage' => ProductionStage::COOKING->value,
            ]);

        $this->actingAs($this->asisten)
            ->post(route('production.batches.actual', $batch), [
                'actual_portions' => 1020,
            ]);

        $this->actingAs($this->asisten)
            ->post(route('production.batches.submit-qc', $batch), [
                'photo' => $photo,
            ]);

        $this->actingAs($this->gizi)
            ->post(route('nutrition.qc.inspect', $batch), [
                'appearance_ok' => 1,
                'aroma_ok' => 1,
                'texture_ok' => 1,
                'taste_tested' => 1,
                'taste_ok' => 1,
                'accepted_portions' => 350,
                'decision_reason' => 'Seluruh parameter organoleptik memenuhi standar mutu prima.',
                'conclusion' => QualityCheckConclusion::SAFE->value,
            ]);

        $this->assertDatabaseHas('activity_logs', ['event' => 'food_batch.created']);
        $this->assertDatabaseHas('activity_logs', ['event' => 'food_batch.stage_advanced']);
        $this->assertDatabaseHas('activity_logs', ['event' => 'food_batch.actual_portions_recorded']);
        $this->assertDatabaseHas('activity_logs', ['event' => 'food_batch.submitted_to_qc']);
        $this->assertDatabaseHas('activity_logs', ['event' => 'quality_check.released']);
        $this->assertDatabaseHas('activity_logs', ['event' => 'production_plan.partial_qc_progress']);
    }
}
