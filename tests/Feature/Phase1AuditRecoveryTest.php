<?php

namespace Tests\Feature;

use App\Enums\FoodBatchStatus;
use App\Enums\IncidentCategory;
use App\Enums\IncidentSeverity;
use App\Enums\MenuStatus;
use App\Enums\QualityCheckConclusion;
use App\Enums\QualityCheckStage;
use App\Enums\SchoolReceiptStatus;
use App\Enums\TrayReturnStatus;
use App\Enums\TripStatus;
use App\Enums\TripStopStatus;
use App\Enums\TripType;
use App\Enums\UserRole;
use App\Models\DeliveryItem;
use App\Models\FoodBatch;
use App\Models\Incident;
use App\Models\Menu;
use App\Models\ProductionAllocation;
use App\Models\ProductionPlan;
use App\Models\QualityCheck;
use App\Models\School;
use App\Models\SchoolServiceRecord;
use App\Models\Sppg;
use App\Models\TrayReturn;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\SchoolHandoverService;
use App\Services\TrayReturnService;
use Carbon\Carbon;
use Database\Seeders\MbgTrackBaselineSeeder;
use Database\Seeders\MenuBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1AuditRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected Sppg $sppg;
    protected School $schoolA;
    protected School $schoolB;
    protected User $driver;
    protected User $picSchoolA;
    protected User $picSchoolB;
    protected Vehicle $vehicle;
    protected Menu $menu;
    protected ProductionPlan $plan;
    protected FoodBatch $batch1;
    protected FoodBatch $batch2;
    protected FoodBatch $batch3;
    protected Trip $trip;
    protected TripStop $stopA;
    protected TripStop $stopB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MbgTrackBaselineSeeder::class);
        $this->seed(MenuBaselineSeeder::class);

        $this->sppg = Sppg::where('code', 'SPPG-SUB-01')->firstOrFail();
        $this->schoolA = School::where('school_code', 'SCH-SDN-01')->firstOrFail();
        $this->schoolB = School::where('school_code', 'SCH-SMPN-02')->firstOrFail();

        $this->driver = User::where('email', 'kurir@mbgtrack.test')->firstOrFail();
        $this->picSchoolA = User::where('email', 'pic.sdn01@mbgtrack.test')->firstOrFail();
        $this->picSchoolB = User::where('email', 'pic.smpn02@mbgtrack.test')->firstOrFail();

        $this->vehicle = Vehicle::firstOrCreate(
            ['sppg_id' => $this->sppg->id, 'plate_number' => 'N 1234 MBG'],
            [
                'brand_model' => 'Grand Max Box Pangan',
                'vehicle_type' => 'VAN',
                'max_capacity_portions' => 1200,
                'ownership_status' => 'SPPG_OWNED',
                'is_active' => true,
            ]
        );

        $this->menu = Menu::where('status', MenuStatus::ACTIVE)->first() ?? Menu::firstOrFail();

        $kepala = User::where('role', UserRole::KEPALA_SPPG)->first();
        $this->plan = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->menu->id,
            'service_date' => Carbon::today(),
            'target_portions' => 800,
            'status' => \App\Enums\ProductionPlanStatus::IN_PRODUCTION,
            'created_by' => $kepala->id,
        ]);

        $this->batch1 = FoodBatch::create([
            'production_plan_id' => $this->plan->id,
            'menu_id' => $this->menu->id,
            'batch_code' => 'BATCH-AUDIT-1',
            'planned_portions' => 300,
            'actual_portions' => 300,
            'status' => FoodBatchStatus::RELEASED,
            'created_by' => $kepala->id,
        ]);

        $this->batch2 = FoodBatch::create([
            'production_plan_id' => $this->plan->id,
            'menu_id' => $this->menu->id,
            'batch_code' => 'BATCH-AUDIT-2',
            'planned_portions' => 300,
            'actual_portions' => 300,
            'status' => FoodBatchStatus::RELEASED,
            'created_by' => $kepala->id,
        ]);

        $this->batch3 = FoodBatch::create([
            'production_plan_id' => $this->plan->id,
            'menu_id' => $this->menu->id,
            'batch_code' => 'BATCH-AUDIT-3',
            'planned_portions' => 200,
            'actual_portions' => 200,
            'status' => FoodBatchStatus::RELEASED,
            'created_by' => $kepala->id,
        ]);

        $this->trip = Trip::create([
            'sppg_id' => $this->sppg->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'trip_code' => 'TRIP-AUDIT-' . uniqid(),
            'trip_type' => TripType::DELIVERY,
            'service_date' => Carbon::today(),
            'status' => TripStatus::IN_PROGRESS,
            'tracking_status' => 'ONLINE',
            'last_location_at' => Carbon::now(),
            'started_at' => Carbon::now()->subMinutes(20),
            'created_by' => $kepala->id,
        ]);

        $this->stopA = TripStop::create([
            'trip_id' => $this->trip->id,
            'school_id' => $this->schoolA->id,
            'sequence_number' => 1,
            'status' => TripStopStatus::ARRIVED,
            'planned_portions' => 340,
            'total_loaded_portions' => 340,
            'arrived_at' => Carbon::now()->subMinutes(15),
        ]);

        $this->stopB = TripStop::create([
            'trip_id' => $this->trip->id,
            'school_id' => $this->schoolB->id,
            'sequence_number' => 2,
            'status' => TripStopStatus::PENDING,
            'planned_portions' => 200,
            'total_loaded_portions' => 200,
        ]);
    }

    /**
     * Helper to allocate batches to stop A.
     */
    protected function setupDeliveryItems(array $batchAllocations): void
    {
        $this->stopA->deliveryItems()->delete();

        $portionReq = \App\Models\DailyPortionRequest::firstOrCreate(
            [
                'school_id' => $this->schoolA->id,
                'sppg_id' => $this->sppg->id,
                'service_date' => Carbon::today(),
            ],
            [
                'registered_count' => 340,
                'requested_portions' => 340,
                'status' => \App\Enums\PortionRequestStatus::CONFIRMED,
                'created_by' => $this->picSchoolA->id,
                'confirmed_at' => now(),
            ]
        );

        $alloc = ProductionAllocation::firstOrCreate(
            [
                'production_plan_id' => $this->plan->id,
                'portion_request_id' => $portionReq->id,
            ],
            [
                'school_id' => $this->schoolA->id,
                'allocated_portions' => 340,
            ]
        );

        foreach ($batchAllocations as $batchId => $portions) {
            DeliveryItem::create([
                'food_batch_id' => $batchId,
                'production_allocation_id' => $alloc->id,
                'trip_stop_id' => $this->stopA->id,
                'planned_portions' => $portions,
                'loaded_portions' => $portions,
                'delivered_portions' => $portions,
                'status' => 'DELIVERED',
            ]);
        }

        $this->stopA->load('deliveryItems');
    }

    /**
     * F01: Test multi-batch with two batches preserves null/unknown portions on aggregate check.
     */
    public function test_f01_multi_batch_two_batches_preserves_unknown_portions(): void
    {
        $this->setupDeliveryItems([
            $this->batch1->id => 200,
            $this->batch2->id => 140,
        ]);

        $handoverService = app(SchoolHandoverService::class);

        $record = $handoverService->confirmReceipt($this->stopA, $this->picSchoolA, [
            'received_portions' => 340,
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        $this->assertEquals(SchoolReceiptStatus::RECEIVED, $record->status);

        // Perform 2nd QC BEFORE_CONSUMPTION in aggregate mode (P05)
        $updatedRecord = $handoverService->recordPreConsumptionCheck($record, $this->picSchoolA, [
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        $this->assertEquals(SchoolReceiptStatus::CLEARED_TO_SERVE, $updatedRecord->status);

        // Fetch BEFORE_CONSUMPTION QC records
        $preChecks = QualityCheck::where('trip_stop_id', $this->stopA->id)
            ->where('check_stage', QualityCheckStage::BEFORE_CONSUMPTION)
            ->get();

        $this->assertCount(2, $preChecks);

        foreach ($preChecks as $qc) {
            // F01 Invariant: Total school portions (340) MUST NOT be written as accepted_portions on every batch!
            $this->assertNull($qc->accepted_portions, "Accepted portions on multi-batch aggregate must be null for batch #{$qc->food_batch_id}");
            $this->assertNull($qc->rejected_portions, "Rejected portions on multi-batch aggregate must be null for batch #{$qc->food_batch_id}");
            $this->assertEquals(QualityCheckConclusion::SAFE, $qc->conclusion);
            $this->assertStringContainsString('unknown (null)', $qc->decision_reason);
        }
    }

    /**
     * F01: Test multi-batch with three batches preserves null/unknown portions on aggregate check.
     */
    public function test_f01_multi_batch_three_batches_preserves_unknown_portions(): void
    {
        $this->setupDeliveryItems([
            $this->batch1->id => 150,
            $this->batch2->id => 100,
            $this->batch3->id => 90,
        ]);

        $handoverService = app(SchoolHandoverService::class);

        $record = $handoverService->confirmReceipt($this->stopA, $this->picSchoolA, [
            'received_portions' => 340,
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        $updatedRecord = $handoverService->recordPreConsumptionCheck($record, $this->picSchoolA, [
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        $this->assertEquals(SchoolReceiptStatus::CLEARED_TO_SERVE, $updatedRecord->status);

        $preChecks = QualityCheck::where('trip_stop_id', $this->stopA->id)
            ->where('check_stage', QualityCheckStage::BEFORE_CONSUMPTION)
            ->get();

        $this->assertCount(3, $preChecks);

        foreach ($preChecks as $qc) {
            $this->assertNull($qc->accepted_portions);
            $this->assertNull($qc->rejected_portions);
            $this->assertEquals(QualityCheckConclusion::SAFE, $qc->conclusion);
        }
    }

    /**
     * F01: Test single batch attributes exact physical portions to the batch.
     */
    public function test_f01_single_batch_attributes_exact_portions(): void
    {
        $this->setupDeliveryItems([
            $this->batch1->id => 340,
        ]);

        $handoverService = app(SchoolHandoverService::class);

        $record = $handoverService->confirmReceipt($this->stopA, $this->picSchoolA, [
            'received_portions' => 340,
            'damaged_portions' => 5,
            'discrepancy_reason' => '5 porsi kemasan rusak saat perjalanan',
            'notes' => '5 porsi kemasan rusak saat perjalanan',
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        $updatedRecord = $handoverService->recordPreConsumptionCheck($record, $this->picSchoolA, [
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        $preChecks = QualityCheck::where('trip_stop_id', $this->stopA->id)
            ->where('check_stage', QualityCheckStage::BEFORE_CONSUMPTION)
            ->get();

        $this->assertCount(1, $preChecks);
        $qc = $preChecks->first();

        // Exact attribution: accepted = received - damaged = 335, rejected = damaged = 5
        $this->assertEquals(335, $qc->accepted_portions);
        $this->assertEquals(5, $qc->rejected_portions);
    }

    /**
     * F01: Test multi-batch with shortage and damage preserves null/unknown portions.
     */
    public function test_f01_multi_batch_shortage_and_damage_preserves_null_portions(): void
    {
        $this->setupDeliveryItems([
            $this->batch1->id => 200,
            $this->batch2->id => 140,
        ]);

        $handoverService = app(SchoolHandoverService::class);

        // Expected 340, physically received 330 with 10 damaged
        $record = $handoverService->confirmReceipt($this->stopA, $this->picSchoolA, [
            'received_portions' => 330,
            'damaged_portions' => 10,
            'discrepancy_reason' => '10 porsi kurang dan 10 porsi rusak',
            'notes' => '10 porsi kurang dan 10 porsi rusak',
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        $updatedRecord = $handoverService->recordPreConsumptionCheck($record, $this->picSchoolA, [
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        $preChecks = QualityCheck::where('trip_stop_id', $this->stopA->id)
            ->where('check_stage', QualityCheckStage::BEFORE_CONSUMPTION)
            ->get();

        $this->assertCount(2, $preChecks);
        foreach ($preChecks as $qc) {
            $this->assertNull($qc->accepted_portions);
            $this->assertNull($qc->rejected_portions);
        }
    }

    /**
     * F01: Test multi-batch with UNSAFE conclusion sets record to HELD and keeps per-batch null.
     */
    public function test_f01_multi_batch_unsafe_conclusion_preserves_null_portions_and_holds_record(): void
    {
        $this->setupDeliveryItems([
            $this->batch1->id => 200,
            $this->batch2->id => 140,
        ]);

        $handoverService = app(SchoolHandoverService::class);

        $record = $handoverService->confirmReceipt($this->stopA, $this->picSchoolA, [
            'received_portions' => 340,
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        // 2nd QC fails because food turned sour/spoilage detected
        $updatedRecord = $handoverService->recordPreConsumptionCheck($record, $this->picSchoolA, [
            'appearance_ok' => true,
            'aroma_ok' => false, // Spoilage odor
            'texture_ok' => true,
            'taste_tested' => false,
            'taste_skipped_reason' => 'Aroma asam tidak wajar tercium saat wadah dibuka.',
            'conclusion' => 'UNSAFE',
            'notes' => 'Aroma asam menyengat, makanan dilarang dibagikan ke siswa.',
        ]);

        $this->assertEquals(SchoolReceiptStatus::HELD, $updatedRecord->status);

        $preChecks = QualityCheck::where('trip_stop_id', $this->stopA->id)
            ->where('check_stage', QualityCheckStage::BEFORE_CONSUMPTION)
            ->get();

        $this->assertCount(2, $preChecks);
        foreach ($preChecks as $qc) {
            $this->assertNull($qc->accepted_portions);
            $this->assertNull($qc->rejected_portions);
            $this->assertEquals(QualityCheckConclusion::UNSAFE, $qc->conclusion);
        }

        // Must trigger a high-severity Food Safety incident
        $incident = Incident::where('category', IncidentCategory::FOOD_SAFETY)
            ->where('subject_type', SchoolServiceRecord::class)
            ->where('subject_id', $updatedRecord->id)
            ->first();

        $this->assertNotNull($incident);
        $this->assertEquals(IncidentSeverity::CRITICAL, $incident->severity);
    }

    /**
     * F02: Test that attempting completeMeal directly from CLEARED_TO_SERVE returns HTTP 409.
     */
    public function test_f02_cannot_complete_meal_directly_from_cleared_to_serve_expects_409(): void
    {
        $this->setupDeliveryItems([$this->batch1->id => 340]);
        $handoverService = app(SchoolHandoverService::class);

        $record = $handoverService->confirmReceipt($this->stopA, $this->picSchoolA, [
            'received_portions' => 340,
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        $clearedRecord = $handoverService->recordPreConsumptionCheck($record, $this->picSchoolA, [
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        $this->assertEquals(SchoolReceiptStatus::CLEARED_TO_SERVE, $clearedRecord->status);
        $this->assertNull($clearedRecord->distribution_started_at);

        // F02 endpoint test: Attempt to complete directly without calling /start
        $response = $this->actingAs($this->picSchoolA)
            ->post(route('school.meal-distribution.complete', $clearedRecord), [
                'served_portions' => 340,
                'leftover_portions' => 0,
            ]);

        $response->assertStatus(409);

        // Verify status remains CLEARED_TO_SERVE and distribution_started_at was NOT created retroactively
        $fresh = $clearedRecord->fresh();
        $this->assertEquals(SchoolReceiptStatus::CLEARED_TO_SERVE, $fresh->status);
        $this->assertNull($fresh->distribution_started_at);
        $this->assertNull($fresh->meal_completed_at);
    }

    /**
     * F02: Test completeMeal after startDistribution succeeds and does not overwrite distribution_started_at.
     */
    public function test_f02_complete_meal_after_start_distribution_succeeds_and_preserves_started_at(): void
    {
        $this->setupDeliveryItems([$this->batch1->id => 340]);
        $handoverService = app(SchoolHandoverService::class);

        $record = $handoverService->confirmReceipt($this->stopA, $this->picSchoolA, [
            'received_portions' => 340,
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        $clearedRecord = $handoverService->recordPreConsumptionCheck($record, $this->picSchoolA, [
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        // Start distribution via official endpoint
        $responseStart = $this->actingAs($this->picSchoolA)
            ->post(route('school.meal-distribution.start', $clearedRecord));
        $responseStart->assertRedirect();

        $distributedRecord = $clearedRecord->fresh();
        $this->assertEquals(SchoolReceiptStatus::DISTRIBUTED, $distributedRecord->status);
        $this->assertNotNull($distributedRecord->distribution_started_at);
        $originalStartedAt = $distributedRecord->distribution_started_at->copy();

        // Complete meal via official endpoint
        $responseComplete = $this->actingAs($this->picSchoolA)
            ->post(route('school.meal-distribution.complete', $distributedRecord), [
                'served_portions' => 338,
                'leftover_portions' => 2,
            ]);
        $responseComplete->assertRedirect();

        $completedRecord = $distributedRecord->fresh();
        $this->assertEquals(SchoolReceiptStatus::MEAL_COMPLETED, $completedRecord->status);
        $this->assertEquals(338, $completedRecord->served_portions);
        $this->assertEquals(2, $completedRecord->leftover_portions);
        $this->assertNotNull($completedRecord->meal_completed_at);
        // Preserved, NOT rewritten retroactively
        $this->assertEquals($originalStartedAt->toIso8601String(), $completedRecord->distribution_started_at->toIso8601String());
    }

    /**
     * F03: Test tasting is rejected unconditionally when appearance_ok is false.
     */
    public function test_f03_tasting_rejected_unconditionally_when_appearance_is_not_ok(): void
    {
        $this->setupDeliveryItems([$this->batch1->id => 340]);
        $handoverService = app(SchoolHandoverService::class);

        $record = $handoverService->confirmReceipt($this->stopA, $this->picSchoolA, [
            'received_portions' => 340,
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        // 1. appearance_ok = false with taste_tested = true and taste_ok = true -> 422
        $resp1 = $this->actingAs($this->picSchoolA)
            ->postJson(route('school.meal-distribution.pre-consumption-check', $record), [
                'appearance_ok' => false,
                'aroma_ok' => true,
                'texture_ok' => true,
                'taste_tested' => true,
                'taste_ok' => true,
                'conclusion' => 'UNSAFE',
            ]);
        $resp1->assertStatus(422);
        $resp1->assertJsonValidationErrors(['taste_tested']);

        // 2. appearance_ok = false with taste_tested = true and taste_ok = false -> 422
        $resp2 = $this->actingAs($this->picSchoolA)
            ->postJson(route('school.meal-distribution.pre-consumption-check', $record), [
                'appearance_ok' => false,
                'aroma_ok' => true,
                'texture_ok' => true,
                'taste_tested' => true,
                'taste_ok' => false,
                'conclusion' => 'UNSAFE',
            ]);
        $resp2->assertStatus(422);
        $resp2->assertJsonValidationErrors(['taste_tested']);

        // 3. appearance_ok = false with taste_tested = true and taste_ok = null -> 422
        $resp3 = $this->actingAs($this->picSchoolA)
            ->postJson(route('school.meal-distribution.pre-consumption-check', $record), [
                'appearance_ok' => false,
                'aroma_ok' => true,
                'texture_ok' => true,
                'taste_tested' => true,
                'taste_ok' => null,
                'conclusion' => 'UNSAFE',
            ]);
        $resp3->assertStatus(422);
        $resp3->assertJsonValidationErrors(['taste_tested']);
    }

    /**
     * F03: Test tasting is rejected unconditionally when aroma_ok is false.
     */
    public function test_f03_tasting_rejected_unconditionally_when_aroma_is_not_ok(): void
    {
        $this->setupDeliveryItems([$this->batch1->id => 340]);
        $handoverService = app(SchoolHandoverService::class);

        $record = $handoverService->confirmReceipt($this->stopA, $this->picSchoolA, [
            'received_portions' => 340,
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        // 1. aroma_ok = false with taste_tested = true and taste_ok = true -> 422
        $resp1 = $this->actingAs($this->picSchoolA)
            ->postJson(route('school.meal-distribution.pre-consumption-check', $record), [
                'appearance_ok' => true,
                'aroma_ok' => false,
                'texture_ok' => true,
                'taste_tested' => true,
                'taste_ok' => true,
                'conclusion' => 'UNSAFE',
            ]);
        $resp1->assertStatus(422);
        $resp1->assertJsonValidationErrors(['taste_tested']);

        // 2. aroma_ok = false with taste_tested = true and taste_ok = false -> 422
        $resp2 = $this->actingAs($this->picSchoolA)
            ->postJson(route('school.meal-distribution.pre-consumption-check', $record), [
                'appearance_ok' => true,
                'aroma_ok' => false,
                'texture_ok' => true,
                'taste_tested' => true,
                'taste_ok' => false,
                'conclusion' => 'UNSAFE',
            ]);
        $resp2->assertStatus(422);
        $resp2->assertJsonValidationErrors(['taste_tested']);

        // 3. aroma_ok = false with taste_tested = true and taste_ok = null -> 422
        $resp3 = $this->actingAs($this->picSchoolA)
            ->postJson(route('school.meal-distribution.pre-consumption-check', $record), [
                'appearance_ok' => true,
                'aroma_ok' => false,
                'texture_ok' => true,
                'taste_tested' => true,
                'taste_ok' => null,
                'conclusion' => 'UNSAFE',
            ]);
        $resp3->assertStatus(422);
        $resp3->assertJsonValidationErrors(['taste_tested']);
    }

    /**
     * Gate B: Test tray return readiness is safely blocked on official endpoint when meal is not completed.
     */
    public function test_gate_b_tray_ready_blocked_when_meal_not_completed_on_official_endpoint(): void
    {
        $this->setupDeliveryItems([$this->batch1->id => 340]);
        $handoverService = app(SchoolHandoverService::class);
        $trayService = app(TrayReturnService::class);

        $record = $handoverService->confirmReceipt($this->stopA, $this->picSchoolA, [
            'received_portions' => 340,
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        $trayReturn = TrayReturn::firstOrCreate(
            ['school_service_record_id' => $record->id],
            [
                'status' => TrayReturnStatus::IN_USE,
                'expected_trays' => 15,
            ]
        );

        // 1. Status is RECEIVED -> 409
        $resp1 = $this->actingAs($this->picSchoolA)
            ->post(route('school.trays.ready', $trayReturn), [
                'ready_count' => 15,
            ]);
        $resp1->assertStatus(409);

        // 2. Status is CLEARED_TO_SERVE -> 409
        $handoverService->recordPreConsumptionCheck($record, $this->picSchoolA, [
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'conclusion' => 'SAFE',
        ]);
        $resp2 = $this->actingAs($this->picSchoolA)
            ->post(route('school.trays.ready', $trayReturn), [
                'ready_count' => 15,
            ]);
        $resp2->assertStatus(409);

        // 3. Status is DISTRIBUTED -> 409
        $handoverService->startDistribution($record->fresh(), $this->picSchoolA);
        $resp3 = $this->actingAs($this->picSchoolA)
            ->post(route('school.trays.ready', $trayReturn), [
                'ready_count' => 15,
            ]);
        $resp3->assertStatus(409);

        // 4. Status is HELD -> 409
        $record->update(['status' => SchoolReceiptStatus::HELD]);
        $resp4 = $this->actingAs($this->picSchoolA)
            ->post(route('school.trays.ready', $trayReturn), [
                'ready_count' => 15,
                'is_safety_exception' => true,
                'notes' => 'Pengecualian tidak resmi',
            ]);
        $resp4->assertStatus(409);
    }

    /**
     * Gate B: Test tray return readiness succeeds on official endpoint when meal is MEAL_COMPLETED.
     */
    public function test_gate_b_tray_ready_succeeds_on_official_endpoint_when_meal_completed(): void
    {
        $this->setupDeliveryItems([$this->batch1->id => 340]);
        $handoverService = app(SchoolHandoverService::class);
        $trayService = app(TrayReturnService::class);

        $record = $handoverService->confirmReceipt($this->stopA, $this->picSchoolA, [
            'received_portions' => 340,
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        $trayReturn = TrayReturn::firstOrCreate(
            ['school_service_record_id' => $record->id],
            [
                'status' => TrayReturnStatus::IN_USE,
                'expected_trays' => 15,
            ]
        );

        $handoverService->recordPreConsumptionCheck($record, $this->picSchoolA, [
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        $handoverService->startDistribution($record->fresh(), $this->picSchoolA);

        $handoverService->completeMeal($record->fresh(), $this->picSchoolA, [
            'served_portions' => 338,
            'leftover_portions' => 2,
        ]);

        $this->assertEquals(SchoolReceiptStatus::MEAL_COMPLETED, $record->fresh()->status);

        // Submit ready trays via official endpoint
        $response = $this->actingAs($this->picSchoolA)
            ->post(route('school.trays.ready', $trayReturn), [
                'ready_count' => 15,
                'damaged_count' => 0,
            ]);

        $response->assertRedirect();
        $this->assertEquals(TrayReturnStatus::READY_FOR_PICKUP, $trayReturn->fresh()->status);
        $this->assertEquals(15, $trayReturn->fresh()->ready_count);
    }

    /**
     * Gate C: Test public map HTML Blade and JSON privacy protections.
     */
    public function test_gate_c_public_map_html_blade_and_json_privacy(): void
    {
        // 1. With school query for School A: HTML Blade renders ONLY School A, zero mention of School B
        $respHtmlSchoolA = $this->get(route('public.trips.map', [
            'tripCode' => $this->trip->trip_code,
            'school' => $this->schoolA->school_code,
        ]));
        $respHtmlSchoolA->assertOk();
        $htmlSchoolA = $respHtmlSchoolA->getContent();

        $this->assertStringContainsString($this->schoolA->name, $htmlSchoolA);
        $this->assertStringNotContainsString($this->schoolB->name, $htmlSchoolA);
        $this->assertStringNotContainsString($this->driver->name, $htmlSchoolA);

        // 2. HTML Blade with invalid/empty school filter: must NOT fall back to all stops!
        $respHtmlInvalid = $this->get(route('public.trips.map', [
            'tripCode' => $this->trip->trip_code,
            'school' => 'UNKNOWN_SCHOOL_CODE',
        ]));
        $respHtmlInvalid->assertOk();
        $htmlInvalid = $respHtmlInvalid->getContent();
        $this->assertStringNotContainsString($this->schoolA->name, $htmlInvalid);
        $this->assertStringNotContainsString($this->schoolB->name, $htmlInvalid);

        // 3. JSON map-data with empty school filter: stops must be empty array (never fall back to all stops)
        $respJsonEmpty = $this->getJson(route('public.trips.map-data', [
            'tripCode' => $this->trip->trip_code,
            'school' => '',
        ]));
        $respJsonEmpty->assertOk();
        $this->assertEmpty($respJsonEmpty->json('stops'));
        $this->assertNull($respJsonEmpty->json('geometry'));

        // 4. JSON map-data with invalid school query: stops must be empty array (never fall back to all stops)
        $respJsonInvalid = $this->getJson(route('public.trips.map-data', [
            'tripCode' => $this->trip->trip_code,
            'school' => 'NON-EXISTENT-SCHOOL',
        ]));
        $respJsonInvalid->assertOk();
        $this->assertEmpty($respJsonInvalid->json('stops'));

        // 5. JSON map-data with valid school query: returns only 1 stop for that school, no sequence number
        $respJsonValid = $this->getJson(route('public.trips.map-data', [
            'tripCode' => $this->trip->trip_code,
            'school' => $this->schoolA->school_code,
        ]));
        $respJsonValid->assertOk();
        $stops = $respJsonValid->json('stops');
        $this->assertCount(1, $stops);
        $this->assertEquals($this->schoolA->name, $stops[0]['school_name']);
        $this->assertArrayNotHasKey('sequence_number', $stops[0]);
        $this->assertNull($stops[0]['lat']);
        $this->assertNull($stops[0]['lng']);
        $this->assertNull($respJsonValid->json('geometry'));
        $this->assertArrayNotHasKey('driver', $respJsonValid->json());
    }
}
