<?php

namespace Tests\Feature;

use App\Enums\FoodBatchStatus;
use App\Enums\MenuStatus;
use App\Enums\QualityCheckConclusion;
use App\Enums\QualityCheckStage;
use App\Enums\SchoolReceiptStatus;
use App\Enums\TripStatus;
use App\Enums\TripStopStatus;
use App\Enums\TripType;
use App\Enums\UserRole;
use App\Models\DailyPortionRequest;
use App\Models\DeliveryItem;
use App\Models\FoodBatch;
use App\Models\Menu;
use App\Models\ProductionAllocation;
use App\Models\ProductionPlan;
use App\Models\QualityCheck;
use App\Models\School;
use App\Models\SchoolServiceRecord;
use App\Models\Sppg;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\SchoolHandoverService;
use Carbon\Carbon;
use Database\Seeders\MbgTrackBaselineSeeder;
use Database\Seeders\MenuBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Pbi10ProvenanceClosureTest extends TestCase
{
    use RefreshDatabase;

    protected Sppg $sppg;
    protected School $school;
    protected User $pic;
    protected User $driver;
    protected Vehicle $vehicle;
    protected Menu $menu;
    protected ProductionPlan $plan;
    protected Trip $trip;
    protected TripStop $stop;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->seed(MbgTrackBaselineSeeder::class);
        $this->seed(MenuBaselineSeeder::class);

        $this->sppg = Sppg::where('code', 'SPPG-SUB-01')->firstOrFail();
        $this->school = School::where('school_code', 'SCH-SDN-01')->firstOrFail();
        $this->pic = User::where('email', 'pic.sdn01@mbgtrack.test')->firstOrFail();
        $this->driver = User::where('email', 'kurir@mbgtrack.test')->firstOrFail();

        $this->vehicle = Vehicle::firstOrCreate(
            ['sppg_id' => $this->sppg->id, 'plate_number' => 'L 9999 MBG'],
            [
                'brand_model' => 'Grand Max Box Pangan',
                'vehicle_type' => 'VAN',
                'max_capacity_portions' => 1200,
                'ownership_status' => 'SPPG_OWNED',
                'is_active' => true,
            ]
        );

        $this->menu = Menu::where('status', MenuStatus::ACTIVE)->first() ?? Menu::firstOrFail();

        $this->plan = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->menu->id,
            'service_date' => Carbon::today(),
            'target_portions' => 1000,
            'status' => \App\Enums\ProductionPlanStatus::IN_PRODUCTION,
            'created_by' => User::where('role', UserRole::KEPALA_SPPG)->first()->id,
        ]);

        $this->trip = Trip::create([
            'sppg_id' => $this->sppg->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'service_date' => Carbon::today(),
            'trip_type' => TripType::DELIVERY,
            'status' => TripStatus::IN_PROGRESS,
            'created_by' => User::where('role', UserRole::KEPALA_SPPG)->first()->id,
            'trip_code' => 'TRIP-CLOSURE-' . uniqid(),
            'started_at' => Carbon::now()->subMinutes(30),
            'tracking_status' => 'ONLINE',
            'active_tracking_session_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $this->stop = TripStop::create([
            'trip_id' => $this->trip->id,
            'school_id' => $this->school->id,
            'sequence_number' => 1,
            'status' => TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
            'planned_portions' => 200,
            'loaded_portions' => 200,
            'arrived_at' => Carbon::now()->subMinutes(10),
        ]);
    }

    protected function createBatch(string $code, int $portions): FoodBatch
    {
        return FoodBatch::create([
            'production_plan_id' => $this->plan->id,
            'menu_id' => $this->menu->id,
            'batch_code' => $code . '-' . uniqid(),
            'planned_portions' => $portions,
            'actual_portions' => $portions,
            'status' => FoodBatchStatus::RELEASED,
            'created_by' => User::where('role', UserRole::ASISTEN_LAPANGAN)->first()->id,
        ]);
    }

    protected function setupItem(FoodBatch $batch, int $loadedPortions): DeliveryItem
    {
        $req = DailyPortionRequest::firstOrCreate(
            ['school_id' => $this->school->id, 'service_date' => Carbon::today()],
            [
                'sppg_id' => $this->sppg->id,
                'registered_count' => 500,
                'requested_portions' => 500,
                'status' => \App\Enums\PortionRequestStatus::CONFIRMED,
                'created_by' => $this->pic->id,
                'confirmed_at' => now(),
            ]
        );

        $alloc = ProductionAllocation::firstOrCreate(
            [
                'production_plan_id' => $this->plan->id,
                'portion_request_id' => $req->id,
            ],
            [
                'school_id' => $this->school->id,
                'allocated_portions' => 500,
            ]
        );

        return DeliveryItem::create([
            'trip_stop_id' => $this->stop->id,
            'food_batch_id' => $batch->id,
            'production_allocation_id' => $alloc->id,
            'planned_portions' => $loadedPortions,
            'loaded_portions' => $loadedPortions,
            'status' => 'LOADED',
        ]);
    }

    /**
     * GATE 1 TEST:
     * Mode B multi-batch: Batch A loaded 100, Batch B loaded 100.
     * PIC confirms total received = 200, damaged = 0 via Mode B (no per-item breakdown).
     * Physical reality: Batch A received 80, Batch B received 120 (total 200).
     * Rule: Mode B aggregate match does NOT prove individual batch counts.
     * Verified:
     * - Stop total receipt is verified (SchoolServiceRecord: 200 received, 0 damaged, RECEIVED).
     * - Stop operational status transitions to HANDED_OVER.
     * - No per-batch verified claim without physical proof:
     *   delivery_items: delivered_portions = null, status = 'UNRESOLVED_DISCREPANCY'.
     *   quality_checks: accepted_portions = null, rejected_portions = null.
     * - decision_reason states stop total receipt verified, but per-batch attribution unknown.
     */
    public function test_gate_1_mode_b_two_batches_total_matches_does_not_falsely_claim_per_batch_verified(): void
    {
        $batchA = $this->createBatch('BATCH-A', 100);
        $batchB = $this->createBatch('BATCH-B', 100);

        $itemA = $this->setupItem($batchA, 100);
        $itemB = $this->setupItem($batchB, 100);

        $this->stop->update(['loaded_portions' => 200, 'planned_portions' => 200]);

        $service = app(SchoolHandoverService::class);
        $serviceRecord = $service->confirmReceipt($this->stop, $this->pic, [
            'received_portions' => 200,
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
            'notes' => 'Penerimaan total 200 porsi menggunakan Mode B agregat.',
        ]);

        $itemA->refresh();
        $itemB->refresh();
        $this->stop->refresh();

        // 1. Stop total receipt is honestly verified
        $this->assertSame(200, $serviceRecord->received_portions);
        $this->assertSame(0, $serviceRecord->damaged_portions);
        $this->assertSame(SchoolReceiptStatus::RECEIVED, $serviceRecord->status);

        // 2. Stop operational status transitions to HANDED_OVER
        $this->assertSame(TripStopStatus::HANDED_OVER, $this->stop->status);
        $this->assertNotNull($this->stop->handed_over_at);

        // 3. Per-batch attribution is UNKNOWN (no false claims!)
        $this->assertNull($itemA->delivered_portions, 'Batch A delivered portions must be null without per-batch verification');
        $this->assertNull($itemB->delivered_portions, 'Batch B delivered portions must be null without per-batch verification');
        $this->assertSame('UNRESOLVED_DISCREPANCY', $itemA->status);
        $this->assertSame('UNRESOLVED_DISCREPANCY', $itemB->status);

        // 4. Quality checks record null accepted and null rejected
        $qcA = QualityCheck::where('trip_stop_id', $this->stop->id)->where('food_batch_id', $batchA->id)->firstOrFail();
        $qcB = QualityCheck::where('trip_stop_id', $this->stop->id)->where('food_batch_id', $batchB->id)->firstOrFail();

        $this->assertNull($qcA->accepted_portions);
        $this->assertNull($qcA->rejected_portions);
        $this->assertNull($qcB->accepted_portions);
        $this->assertNull($qcB->rejected_portions);

        $this->assertStringContainsString('Penerimaan total fisik tingkat stop terverifikasi lengkap (200 porsi)', $qcA->decision_reason);
        $this->assertStringContainsString('pemeriksaan fisik per batch tidak dilakukan (Mode B: Aggregate)', $qcA->decision_reason);
        $this->assertStringContainsString('atribusi porsi delivered dan QC per batch berstatus unknown', $qcA->decision_reason);
    }

    /**
     * GATE 1 COMPLEMENT:
     * Mode A with genuine physical per-batch verification records legitimate delivered and QC portions.
     */
    public function test_gate_1_mode_a_with_genuine_per_batch_verification_records_legitimate_receipt(): void
    {
        $batchA = $this->createBatch('BATCH-A', 100);
        $batchB = $this->createBatch('BATCH-B', 100);

        $itemA = $this->setupItem($batchA, 100);
        $itemB = $this->setupItem($batchB, 100);

        $this->stop->update(['loaded_portions' => 200, 'planned_portions' => 200]);

        $service = app(SchoolHandoverService::class);
        $serviceRecord = $service->confirmReceipt($this->stop, $this->pic, [
            'received_portions' => 200,
            'damaged_portions' => 0,
            'items' => [
                $itemA->id => ['received_portions' => 100, 'damaged_portions' => 0],
                $itemB->id => ['received_portions' => 100, 'damaged_portions' => 0],
            ],
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
            'notes' => 'Pemeriksaan fisik per kontainer batch terverifikasi.',
        ]);

        $itemA->refresh();
        $itemB->refresh();

        $this->assertSame(100, $itemA->delivered_portions);
        $this->assertSame(100, $itemB->delivered_portions);
        $this->assertSame('DELIVERED', $itemA->status);
        $this->assertSame('DELIVERED', $itemB->status);

        $qcA = QualityCheck::where('trip_stop_id', $this->stop->id)->where('food_batch_id', $batchA->id)->firstOrFail();
        $qcB = QualityCheck::where('trip_stop_id', $this->stop->id)->where('food_batch_id', $batchB->id)->firstOrFail();

        $this->assertSame(100, $qcA->accepted_portions);
        $this->assertSame(0, $qcA->rejected_portions);
        $this->assertSame(100, $qcB->accepted_portions);
        $this->assertSame(0, $qcB->rejected_portions);
    }

    /**
     * GATE 2 TEST:
     * Damage mixed with surplus does NOT automatically belong to manifest batch.
     * Batch A loaded 10, Batch B loaded 90 (Stop expected = 100).
     * PIC reports Batch A received 40, damaged 30 (good = 10). Surplus = 30.
     * Batch B received 60, damaged 0.
     * Stop total received = 100, damaged = 30.
     * Physical reality: 30 damaged and 10 undamaged are mixed within Batch A's 40 portions.
     * Arithmetic min() cannot determine whether manifest 10 belongs to the 30 damaged or 10 good.
     * Verified:
     * - No false arithmetic attribution.
     * - delivered_portions = null, status = 'UNRESOLVED_DISCREPANCY'.
     * - QualityCheck: accepted_portions = null, rejected_portions = null.
     * - All 6 required audit figures documented:
     *   1. Jumlah loaded resmi: 10 porsi
     *   2. Jumlah fisik observed: 40 porsi
     *   3. Jumlah damaged observed: 30 porsi
     *   4. Jumlah surplus: 30 porsi
     *   5. Jumlah yang benar-benar dapat diatribusikan: 0 porsi
     *   6. Jumlah yang belum bisa diatribusikan: 10 porsi
     * - Physical receipt count is preserved (100 received, 30 damaged).
     * - Stop operational status remains HANDED_OVER.
     */
    public function test_gate_2_mixed_surplus_and_damage_does_not_produce_false_attribution(): void
    {
        $batchA = $this->createBatch('BATCH-SURPLUS-A', 10);
        $batchB = $this->createBatch('BATCH-SURPLUS-B', 90);

        $itemA = $this->setupItem($batchA, 10);
        $itemB = $this->setupItem($batchB, 90);

        $this->stop->update(['loaded_portions' => 100, 'planned_portions' => 100]);

        $service = app(SchoolHandoverService::class);
        $serviceRecord = $service->confirmReceipt($this->stop, $this->pic, [
            'received_portions' => 100,
            'damaged_portions' => 30,
            'items' => [
                $itemA->id => ['received_portions' => 40, 'damaged_portions' => 30],
                $itemB->id => ['received_portions' => 60, 'damaged_portions' => 0],
            ],
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
            'notes' => 'Surplus muatan 30 porsi pada Batch A dengan 30 porsi rusak tercampur.',
        ]);

        $itemA->refresh();
        $itemB->refresh();
        $this->stop->refresh();

        // Total physical receipt is faithfully recorded on service record
        $this->assertSame(100, $serviceRecord->received_portions);
        $this->assertSame(30, $serviceRecord->damaged_portions);
        $this->assertSame(SchoolReceiptStatus::RECEIVED_WITH_DISCREPANCY, $serviceRecord->status);

        // Operational status transitions as expected by v4 documentation
        $this->assertSame(TripStopStatus::HANDED_OVER, $this->stop->status);

        // Provenance attribution is unknown on Batch A - no false min() arithmetic!
        $this->assertNull($itemA->delivered_portions, 'Delivered portions must be null when surplus is mixed with damage');
        $this->assertSame('UNRESOLVED_DISCREPANCY', $itemA->status);

        $qcA = QualityCheck::where('trip_stop_id', $this->stop->id)->where('food_batch_id', $batchA->id)->firstOrFail();
        $this->assertNull($qcA->accepted_portions);
        $this->assertNull($qcA->rejected_portions);

        // All 6 required audit figures recorded in decision reason
        $this->assertStringContainsString('Jumlah loaded resmi: 10 porsi.', $qcA->decision_reason);
        $this->assertStringContainsString('Jumlah fisik observed: 40 porsi.', $qcA->decision_reason);
        $this->assertStringContainsString('Jumlah damaged observed: 30 porsi.', $qcA->decision_reason);
        $this->assertStringContainsString('Jumlah surplus: 30 porsi.', $qcA->decision_reason);
        $this->assertStringContainsString('Jumlah yang benar-benar dapat diatribusikan: 0 porsi.', $qcA->decision_reason);
        $this->assertStringContainsString('Jumlah yang belum bisa diatribusikan: 10 porsi.', $qcA->decision_reason);

        // Batch B (no surplus, 0 damage) is verified
        $this->assertSame(60, $itemB->delivered_portions);
        $this->assertSame('DELIVERED', $itemB->status);
        $qcB = QualityCheck::where('trip_stop_id', $this->stop->id)->where('food_batch_id', $batchB->id)->firstOrFail();
        $this->assertSame(60, $qcB->accepted_portions);
        $this->assertSame(0, $qcB->rejected_portions);
    }

    /**
     * GATE 2 COMPLEMENT A:
     * Surplus with 0 damaged portions: all observed portions are good,
     * so manifest portions are safely attributed as good.
     */
    public function test_gate_2_pure_surplus_without_damage_attributes_safely(): void
    {
        $batchA = $this->createBatch('BATCH-SURPLUS-A', 10);
        $batchB = $this->createBatch('BATCH-SURPLUS-B', 90);

        $itemA = $this->setupItem($batchA, 10);
        $itemB = $this->setupItem($batchB, 90);

        $this->stop->update(['loaded_portions' => 100, 'planned_portions' => 100]);

        $service = app(SchoolHandoverService::class);
        $serviceRecord = $service->confirmReceipt($this->stop, $this->pic, [
            'received_portions' => 100,
            'damaged_portions' => 0,
            'items' => [
                $itemA->id => ['received_portions' => 40, 'damaged_portions' => 0],
                $itemB->id => ['received_portions' => 60, 'damaged_portions' => 0],
            ],
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
            'notes' => 'Surplus muatan 30 porsi dalam kondisi 100% baik.',
        ]);

        $itemA->refresh();
        $this->assertSame(10, $itemA->delivered_portions);
        $this->assertSame('DELIVERED', $itemA->status);

        $qcA = QualityCheck::where('trip_stop_id', $this->stop->id)->where('food_batch_id', $batchA->id)->firstOrFail();
        $this->assertSame(10, $qcA->accepted_portions);
        $this->assertSame(0, $qcA->rejected_portions);

        $this->assertStringContainsString('Jumlah loaded resmi: 10 porsi.', $qcA->decision_reason);
        $this->assertStringContainsString('Jumlah fisik observed: 40 porsi.', $qcA->decision_reason);
        $this->assertStringContainsString('Jumlah damaged observed: 0 porsi.', $qcA->decision_reason);
        $this->assertStringContainsString('Jumlah surplus: 30 porsi.', $qcA->decision_reason);
        $this->assertStringContainsString('Jumlah yang benar-benar dapat diatribusikan: 10 porsi.', $qcA->decision_reason);
        $this->assertStringContainsString('Jumlah yang belum bisa diatribusikan: 0 porsi.', $qcA->decision_reason);
    }

    /**
     * GATE 2 COMPLEMENT B:
     * Surplus with 100% damaged portions: all observed portions are damaged,
     * so manifest portions are safely attributed as damaged.
     */
    public function test_gate_2_pure_surplus_with_all_damaged_attributes_damage_safely(): void
    {
        $batchA = $this->createBatch('BATCH-SURPLUS-A', 10);
        $batchB = $this->createBatch('BATCH-SURPLUS-B', 90);

        $itemA = $this->setupItem($batchA, 10);
        $itemB = $this->setupItem($batchB, 90);

        $this->stop->update(['loaded_portions' => 100, 'planned_portions' => 100]);

        $service = app(SchoolHandoverService::class);
        $serviceRecord = $service->confirmReceipt($this->stop, $this->pic, [
            'received_portions' => 100,
            'damaged_portions' => 40,
            'items' => [
                $itemA->id => ['received_portions' => 40, 'damaged_portions' => 40],
                $itemB->id => ['received_portions' => 60, 'damaged_portions' => 0],
            ],
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
            'notes' => 'Surplus muatan 40 porsi seluruhnya rusak.',
        ]);

        $itemA->refresh();
        $this->assertSame(10, $itemA->delivered_portions);
        $this->assertSame('DELIVERED', $itemA->status);

        $qcA = QualityCheck::where('trip_stop_id', $this->stop->id)->where('food_batch_id', $batchA->id)->firstOrFail();
        $this->assertSame(0, $qcA->accepted_portions);
        $this->assertSame(10, $qcA->rejected_portions);

        $this->assertStringContainsString('Jumlah loaded resmi: 10 porsi.', $qcA->decision_reason);
        $this->assertStringContainsString('Jumlah fisik observed: 40 porsi.', $qcA->decision_reason);
        $this->assertStringContainsString('Jumlah damaged observed: 40 porsi.', $qcA->decision_reason);
        $this->assertStringContainsString('Jumlah surplus: 30 porsi.', $qcA->decision_reason);
        $this->assertStringContainsString('Jumlah yang benar-benar dapat diatribusikan: 10 porsi.', $qcA->decision_reason);
        $this->assertStringContainsString('Jumlah yang belum bisa diatribusikan: 0 porsi.', $qcA->decision_reason);
    }

    /**
     * Single batch surplus with mixed damage on Mode B.
     */
    public function test_gate_2_single_batch_mixed_surplus_and_damage(): void
    {
        $batch = $this->createBatch('BATCH-SINGLE-SURPLUS', 100);
        $item = $this->setupItem($batch, 100);

        $this->stop->update(['loaded_portions' => 100, 'planned_portions' => 100]);

        $service = app(SchoolHandoverService::class);
        $serviceRecord = $service->confirmReceipt($this->stop, $this->pic, [
            'received_portions' => 120, // 20 portions surplus (within 150%)
            'damaged_portions' => 50,  // 50 damaged, 70 good
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
            'notes' => 'Single batch surplus 20 porsi dengan 50 porsi rusak tercampur.',
        ]);

        $item->refresh();
        $this->assertNull($item->delivered_portions);
        $this->assertSame('UNRESOLVED_DISCREPANCY', $item->status);

        $qc = QualityCheck::where('trip_stop_id', $this->stop->id)->where('food_batch_id', $batch->id)->firstOrFail();
        $this->assertNull($qc->accepted_portions);
        $this->assertNull($qc->rejected_portions);

        $this->assertStringContainsString('Jumlah loaded resmi: 100 porsi.', $qc->decision_reason);
        $this->assertStringContainsString('Jumlah fisik observed: 120 porsi.', $qc->decision_reason);
        $this->assertStringContainsString('Jumlah damaged observed: 50 porsi.', $qc->decision_reason);
        $this->assertStringContainsString('Jumlah surplus: 20 porsi.', $qc->decision_reason);
        $this->assertStringContainsString('Jumlah yang benar-benar dapat diatribusikan: 0 porsi.', $qc->decision_reason);
        $this->assertStringContainsString('Jumlah yang belum bisa diatribusikan: 100 porsi.', $qc->decision_reason);
    }

    /**
     * Multi-stop workflow test:
     * Transitioning Stop 1 activates next stop as EN_ROUTE without regression.
     */
    public function test_operational_status_transitions_and_next_stop_activation_preserved(): void
    {
        $school2 = School::where('school_code', 'SCH-SMPN-02')->firstOrFail();
        $stop2 = TripStop::create([
            'trip_id' => $this->trip->id,
            'school_id' => $school2->id,
            'sequence_number' => 2,
            'status' => TripStopStatus::PENDING,
            'planned_portions' => 150,
            'loaded_portions' => 150,
        ]);

        $batch = $this->createBatch('BATCH-MULTI-STOP', 200);
        $item = $this->setupItem($batch, 200);

        $service = app(SchoolHandoverService::class);
        $service->confirmReceipt($this->stop, $this->pic, [
            'received_portions' => 200,
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        $this->stop->refresh();
        $stop2->refresh();

        $this->assertSame(TripStopStatus::HANDED_OVER, $this->stop->status);
        $this->assertSame(TripStopStatus::EN_ROUTE, $stop2->status);
    }
}
