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
use App\Models\EvidencePhoto;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Pbi10FinalReconciliationGateTest extends TestCase
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
            'trip_code' => 'TRIP-RECON-' . uniqid(),
            'started_at' => Carbon::now()->subMinutes(30),
            'tracking_status' => 'ONLINE',
            'active_tracking_session_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $this->stop = TripStop::create([
            'trip_id' => $this->trip->id,
            'school_id' => $this->school->id,
            'sequence_number' => 1,
            'status' => TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
            'planned_portions' => 300,
            'loaded_portions' => 300,
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

    protected function setupItem(FoodBatch $batch, int $loadedPortions, int $plannedPortions = 0): DeliveryItem
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
            'planned_portions' => $plannedPortions ?: $loadedPortions,
            'loaded_portions' => $loadedPortions,
        ]);
    }

    /**
     * GATE A - Scenario 1: Three batches, total accepted = 101.
     * Rounding 101/3 in previous code yielded 34 each (total 102), inventing a phantom portion.
     * Test verifies: Sum of QC accepted_portions MUST NOT exceed total accepted (101).
     */
    public function test_gate_a_scenario_1_three_batches_no_rounding_drift_101(): void
    {
        $batch1 = $this->createBatch('BATCH-101-A', 34);
        $batch2 = $this->createBatch('BATCH-101-B', 34);
        $batch3 = $this->createBatch('BATCH-101-C', 33);

        $item1 = $this->setupItem($batch1, 34);
        $item2 = $this->setupItem($batch2, 34);
        $item3 = $this->setupItem($batch3, 33);

        $this->stop->update(['loaded_portions' => 101, 'planned_portions' => 101]);

        $service = app(SchoolHandoverService::class);
        $serviceRecord = $service->confirmReceipt($this->stop, $this->pic, [
            'received_portions' => 101,
            'damaged_portions' => 0,
            'items' => [
                $item1->id => ['received_portions' => 34, 'damaged_portions' => 0],
                $item2->id => ['received_portions' => 34, 'damaged_portions' => 0],
                $item3->id => ['received_portions' => 33, 'damaged_portions' => 0],
            ],
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        $qcs = QualityCheck::where('trip_stop_id', $this->stop->id)->get();
        $this->assertCount(3, $qcs);

        $sumAccepted = $qcs->sum('accepted_portions');
        $this->assertEquals(101, $sumAccepted, "Sum of QC accepted_portions must equal 101 exactly, NOT 102!");
        $this->assertNotEquals(102, $sumAccepted, "Phantom portion detected: 101 became 102 due to rounding!");
    }

    /**
     * GATE A - Scenario 2: Batch A loaded 90, Batch B loaded 10. Total accepted 100.
     * Previous code wrote round(100/2) = 50 for Batch B, exceeding its loaded portions by 5x!
     */
    public function test_gate_a_scenario_2_small_batch_never_exceeds_physical_loaded(): void
    {
        $batchA = $this->createBatch('BATCH-90', 90);
        $batchB = $this->createBatch('BATCH-10', 10);

        $itemA = $this->setupItem($batchA, 90);
        $itemB = $this->setupItem($batchB, 10);

        $this->stop->update(['loaded_portions' => 100, 'planned_portions' => 100]);

        $service = app(SchoolHandoverService::class);
        $serviceRecord = $service->confirmReceipt($this->stop, $this->pic, [
            'received_portions' => 100,
            'damaged_portions' => 0,
            'items' => [
                $itemA->id => ['received_portions' => 90, 'damaged_portions' => 0],
                $itemB->id => ['received_portions' => 10, 'damaged_portions' => 0],
            ],
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        $qcB = QualityCheck::where('trip_stop_id', $this->stop->id)
            ->where('food_batch_id', $batchB->id)
            ->firstOrFail();

        $this->assertLessThanOrEqual(10, $qcB->accepted_portions, "Batch B QC accepted cannot exceed its physical loaded 10 portions!");
        $this->assertNotEquals(50, $qcB->accepted_portions, "Fatal: Batch B received 50 portions accepted from naive equal split!");
    }

    /**
     * GATE A - Scenario 3: Batch A 100, Batch B 80, Batch C 70. Total loaded = 250.
     * Received 240, Damaged 11.
     * Without verified per-batch proof, system must NOT invent equal split (229/3=76, 11/3=4).
     * It must record aggregate unresolved discrepancy on stop level.
     */
    public function test_gate_a_scenario_3_unattributed_shortage_and_damage_does_not_fabricate_batch_split(): void
    {
        $batchA = $this->createBatch('BATCH-250-A', 100);
        $batchB = $this->createBatch('BATCH-250-B', 80);
        $batchC = $this->createBatch('BATCH-250-C', 70);

        $this->setupItem($batchA, 100);
        $this->setupItem($batchB, 80);
        $this->setupItem($batchC, 70);

        $this->stop->update(['loaded_portions' => 250, 'planned_portions' => 250]);

        $service = app(SchoolHandoverService::class);
        $serviceRecord = $service->confirmReceipt($this->stop, $this->pic, [
            'received_portions' => 240,
            'damaged_portions' => 11,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
            'notes' => 'Terdapat kekurangan 10 porsi dan 11 porsi rusak.',
        ]);

        $this->assertEquals(SchoolReceiptStatus::RECEIVED_WITH_DISCREPANCY, $serviceRecord->status);
        $this->assertEquals(240, $serviceRecord->received_portions);
        $this->assertEquals(11, $serviceRecord->damaged_portions);
        $this->assertEquals(10, $serviceRecord->shortage_portions);

        $qcs = QualityCheck::where('trip_stop_id', $this->stop->id)->get();
        foreach ($qcs as $qc) {
            // Unattributed multi-batch QC must NOT invent a 76/4 split
            $this->assertNotEquals(4, $qc->rejected_portions, "Damage portion was naively fabricated across batches without physical proof!");
        }
    }

    /**
     * GATE B: Delivered portions can NEVER exceed loaded portions per item, even on over-receipt.
     * Item A: loaded = 100. PIC receives 110.
     * Item A delivered_portions must be <= 100 (never 110!).
     */
    public function test_gate_b_delivered_portions_cannot_exceed_loaded_per_item_on_over_receipt(): void
    {
        $batch = $this->createBatch('BATCH-OVER', 100);
        $item = $this->setupItem($batch, 100);

        $this->stop->update(['loaded_portions' => 100, 'planned_portions' => 100]);

        $service = app(SchoolHandoverService::class);
        $serviceRecord = $service->confirmReceipt($this->stop, $this->pic, [
            'received_portions' => 110,
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
            'notes' => 'Kelebihan 10 porsi tambahan dari dapur SPPG.',
        ]);

        $item->refresh();
        $this->assertLessThanOrEqual(100, $item->delivered_portions, "Delivered portions on item cannot exceed loaded portions (100)!");
        $this->assertNotEquals(110, $item->delivered_portions, "Fatal: Item was allocated 110 delivered when only 100 was loaded!");
        $this->assertEquals(110, $serviceRecord->received_portions, "Observed physical receipt is honestly recorded on service record.");
        $this->assertEquals(SchoolReceiptStatus::RECEIVED_WITH_DISCREPANCY, $serviceRecord->status);
    }

    /**
     * GATE C: Item loaded = 0, planned = 100 must NOT receive delivered portions.
     * Item A: planned = 100, loaded = 0.
     * Item B: planned = 100, loaded = 100.
     * PIC receives 100. Item A delivered MUST be 0. Item B delivered MUST be 100.
     */
    public function test_gate_c_zero_loaded_item_never_receives_delivered_portions(): void
    {
        $batchA = $this->createBatch('BATCH-UNLOADED', 100);
        $batchB = $this->createBatch('BATCH-LOADED', 100);

        $itemA = $this->setupItem($batchA, 0, 100); // planned 100, loaded 0
        $itemB = $this->setupItem($batchB, 100, 100); // planned 100, loaded 100

        $this->stop->update(['loaded_portions' => 100, 'planned_portions' => 200]);

        $service = app(SchoolHandoverService::class);
        $service->confirmReceipt($this->stop, $this->pic, [
            'received_portions' => 100,
            'damaged_portions' => 0,
            'items' => [
                $itemA->id => ['received_portions' => 0, 'damaged_portions' => 0],
                $itemB->id => ['received_portions' => 100, 'damaged_portions' => 0],
            ],
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        $itemA->refresh();
        $itemB->refresh();

        $this->assertEquals(0, $itemA->delivered_portions, "Item A had 0 loaded portions; delivered_portions MUST be 0!");
        $this->assertEquals(100, $itemB->delivered_portions, "Item B had 100 loaded portions; delivered_portions MUST be 100!");
    }

    /**
     * GATE E: Evidence storage transactional safety.
     * When database throws an exception during confirmReceipt or markStopArrival,
     * the uploaded file must be cleaned up from disk and not left orphaned.
     */
    public function test_gate_e_evidence_file_cleaned_up_if_db_transaction_fails(): void
    {
        $batch = $this->createBatch('BATCH-ERR', 100);
        $this->setupItem($batch, 100);

        $file = UploadedFile::fake()->image('evidence.jpg', 600, 400);

        // Force a DB exception inside confirmReceipt by injecting an invalid column or listener
        DB::listen(function ($query) {
            if (str_contains($query->sql, 'school_service_records') && str_contains($query->sql, 'insert')) {
                throw new \RuntimeException("Simulated database failure during receipt insertion");
            }
        });

        $service = app(SchoolHandoverService::class);

        try {
            $service->confirmReceipt($this->stop, $this->pic, [
                'received_portions' => 100,
                'damaged_portions' => 0,
                'appearance_ok' => true,
                'aroma_ok' => true,
                'texture_ok' => true,
                'taste_tested' => true,
                'taste_ok' => true,
                'conclusion' => 'SAFE',
            ], $file);
            $this->fail("Expected RuntimeException was not thrown");
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString("Simulated database failure", $e->getMessage());
        }

        // Verify that no orphan files exist in evidence_photos directory on disk
        $files = Storage::disk('local')->allFiles('evidence_photos');
        $this->assertEmpty($files, "Orphan file found in storage after DB transaction rollback!");
    }

    /**
     * GATE A & D: Scenario 3 with verified per-batch attribution.
     * When PIC observes and verifies per-batch physical counts,
     * QualityChecks and delivery items reflect exact physical evidence.
     */
    public function test_gate_a_scenario_3_verified_per_batch_attribution(): void
    {
        $batchA = $this->createBatch('BATCH-VERIF-A', 100);
        $batchB = $this->createBatch('BATCH-VERIF-B', 80);
        $batchC = $this->createBatch('BATCH-VERIF-C', 70);

        $itemA = $this->setupItem($batchA, 100);
        $itemB = $this->setupItem($batchB, 80);
        $itemC = $this->setupItem($batchC, 70);

        $this->stop->update(['loaded_portions' => 250, 'planned_portions' => 250]);

        $service = app(SchoolHandoverService::class);
        $serviceRecord = $service->confirmReceipt($this->stop, $this->pic, [
            'received_portions' => 240,
            'damaged_portions' => 11,
            'items' => [
                $itemA->id => ['received_portions' => 100, 'damaged_portions' => 0],
                $itemB->id => ['received_portions' => 80, 'damaged_portions' => 11],
                $itemC->id => ['received_portions' => 60, 'damaged_portions' => 0],
            ],
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
            'notes' => 'Shortage 10 porsi pada Batch C dan 11 porsi rusak pada Batch B terverifikasi fisik.',
        ]);

        $itemA->refresh();
        $itemB->refresh();
        $itemC->refresh();

        $this->assertEquals(100, $itemA->delivered_portions);
        $this->assertEquals(80, $itemB->delivered_portions);
        $this->assertEquals(60, $itemC->delivered_portions);

        $qcA = QualityCheck::where('trip_stop_id', $this->stop->id)->where('food_batch_id', $batchA->id)->firstOrFail();
        $qcB = QualityCheck::where('trip_stop_id', $this->stop->id)->where('food_batch_id', $batchB->id)->firstOrFail();
        $qcC = QualityCheck::where('trip_stop_id', $this->stop->id)->where('food_batch_id', $batchC->id)->firstOrFail();

        $this->assertEquals(100, $qcA->accepted_portions);
        $this->assertEquals(0, $qcA->rejected_portions);

        $this->assertEquals(69, $qcB->accepted_portions);
        $this->assertEquals(11, $qcB->rejected_portions);

        $this->assertEquals(60, $qcC->accepted_portions);
        $this->assertEquals(0, $qcC->rejected_portions);

        $sumAccepted = $qcA->accepted_portions + $qcB->accepted_portions + $qcC->accepted_portions;
        $sumRejected = $qcA->rejected_portions + $qcB->rejected_portions + $qcC->rejected_portions;

        $this->assertEquals(229, $sumAccepted, "Total accepted portions must equal 240 received - 11 damaged = 229.");
        $this->assertEquals(11, $sumRejected, "Total rejected portions must equal 11 damaged.");
    }

    /**
     * GATE B: Multi-batch over-receipt strictly capped at loaded portions per item.
     * Batch A: loaded 50, Batch B: loaded 50. Total loaded = 100.
     * PIC receives 120 (over-receipt 20).
     * Invariants:
     * itemA.delivered <= 50, itemB.delivered <= 50.
     * Total delivered across items = 100 (never 120!).
     */
    public function test_gate_b_multi_batch_over_receipt_strictly_capped_at_loaded(): void
    {
        $batchA = $this->createBatch('BATCH-CAP-A', 50);
        $batchB = $this->createBatch('BATCH-CAP-B', 50);

        $itemA = $this->setupItem($batchA, 50);
        $itemB = $this->setupItem($batchB, 50);

        $this->stop->update(['loaded_portions' => 100, 'planned_portions' => 100]);

        $service = app(SchoolHandoverService::class);
        $serviceRecord = $service->confirmReceipt($this->stop, $this->pic, [
            'received_portions' => 120,
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
            'notes' => 'Tambahan 20 porsi ekstra dari SPPG.',
        ]);

        $itemA->refresh();
        $itemB->refresh();

        // GATE 2: Multi-batch over-receipt without per-batch verification leaves delivered_portions null (unresolved discrepancy)
        $this->assertNull($itemA->delivered_portions, "Item A delivered must be null when attribution unresolved");
        $this->assertNull($itemB->delivered_portions, "Item B delivered must be null when attribution unresolved");
        $this->assertSame('UNRESOLVED_DISCREPANCY', $itemA->status);
        $this->assertSame('UNRESOLVED_DISCREPANCY', $itemB->status);
        $this->assertEquals(120, $serviceRecord->received_portions);
        $this->assertEquals(SchoolReceiptStatus::RECEIVED_WITH_DISCREPANCY, $serviceRecord->status);
    }

    /**
     * GATE C: Item loaded_portions = null must NOT receive delivered portions.
     */
    public function test_gate_c_null_loaded_item_never_receives_delivered_portions(): void
    {
        $batchA = $this->createBatch('BATCH-NULL-LOAD', 100);
        $batchB = $this->createBatch('BATCH-VALID-LOAD', 100);

        $itemA = $this->setupItem($batchA, 0, 100);
        $itemA->update(['loaded_portions' => null]); // Explicitly null
        $itemB = $this->setupItem($batchB, 100, 100);

        $this->stop->update(['loaded_portions' => 100, 'planned_portions' => 200]);

        $service = app(SchoolHandoverService::class);
        $service->confirmReceipt($this->stop, $this->pic, [
            'received_portions' => 100,
            'damaged_portions' => 0,
            'items' => [
                $itemA->id => ['received_portions' => 0, 'damaged_portions' => 0],
                $itemB->id => ['received_portions' => 100, 'damaged_portions' => 0],
            ],
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        $itemA->refresh();
        $itemB->refresh();

        $this->assertEquals(0, (int) $itemA->delivered_portions, "Item with null loaded portions cannot receive delivered portions!");
        $this->assertEquals(100, (int) $itemB->delivered_portions, "Item with 100 loaded portions receives 100 delivered!");
    }

    /**
     * GATE E: Evidence file cleanup when DB fails during markStopArrival.
     */
    public function test_gate_e_mark_stop_arrival_evidence_file_cleaned_up_if_db_fails(): void
    {
        $batch = $this->createBatch('BATCH-ARR-ERR', 100);
        $this->setupItem($batch, 100);

        $file = UploadedFile::fake()->image('arrival.jpg', 600, 400);

        $this->stop->update(['status' => TripStopStatus::PENDING]);

        DB::listen(function ($query) {
            if (str_contains($query->sql, 'activity_logs') && str_contains($query->sql, 'insert')) {
                throw new \RuntimeException("Simulated failure during activity log on arrival");
            }
        });

        $service = app(SchoolHandoverService::class);

        try {
            $service->markStopArrival($this->stop, $this->driver, $file, 'Foto kedatangan');
            $this->fail("Expected RuntimeException was not thrown");
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString("Simulated failure during activity log", $e->getMessage());
        }

        $files = Storage::disk('local')->allFiles('evidence_photos');
        $this->assertEmpty($files, "Orphan arrival photo found on disk after rollback!");
    }

    /**
     * GATE F: Accurate electronic receipt confirmation terminology in UI.
     */
    public function test_gate_f_ui_displays_accurate_electronic_confirmation_terminology(): void
    {
        $batch = $this->createBatch('BATCH-TERM', 100);
        $this->setupItem($batch, 100);

        // Before confirmation (form view)
        $response = $this->actingAs($this->pic)->get(route('school.receipts.show', $this->stop));
        $response->assertStatus(200);
        $response->assertSee('Konfirmasi Penerimaan Elektronik');

        // Confirm receipt
        $service = app(SchoolHandoverService::class);
        $service->confirmReceipt($this->stop, $this->pic, [
            'received_portions' => 100,
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        // After confirmation (certificate/record view)
        $responseAfter = $this->actingAs($this->pic)->get(route('school.receipts.show', $this->stop));
        $responseAfter->assertStatus(200);
        $responseAfter->assertSee('Berita Acara Penerimaan Tercatat');
        $responseAfter->assertSee('Konfirmasi Elektronik');
        $responseAfter->assertSee('Petugas Penerima (Autentikasi Akun)');
    }
}

