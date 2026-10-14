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
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Pbi10PartialInputAndProvenanceReproductionTest extends TestCase
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
    protected DeliveryItem $itemA;
    protected DeliveryItem $itemB;
    protected FoodBatch $batchA;
    protected FoodBatch $batchB;

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
            ['plate_number' => 'L 9999 MBG'],
            [
                'sppg_id' => $this->sppg->id,
                'vehicle_name' => 'Grand Max Box Pangan',
                'vehicle_type' => 'VAN',
                'capacity_portions' => 1200,
                'is_active' => true,
            ]
        );

        $this->menu = Menu::where('status', MenuStatus::ACTIVE)->first() ?? Menu::firstOrFail();

        $this->plan = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->menu->id,
            'service_date' => Carbon::today(),
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
        ]);

        $this->stop = TripStop::create([
            'trip_id' => $this->trip->id,
            'school_id' => $this->school->id,
            'stop_type' => 'DELIVERY',
            'sequence_number' => 1,
            'planned_portions' => 150,
            'loaded_portions' => 150,
            'arrived_at' => Carbon::now()->subMinutes(10),
            'status' => TripStopStatus::ARRIVED,
        ]);

        $portionReq = DailyPortionRequest::create([
            'school_id' => $this->school->id,
            'service_date' => Carbon::today(),
            'registered_count' => 150,
            'requested_portions' => 150,
            'status' => \App\Enums\PortionRequestStatus::CONFIRMED,
        ]);

        $allocation = ProductionAllocation::create([
            'production_plan_id' => $this->plan->id,
            'school_id' => $this->school->id,
            'portion_request_id' => $portionReq->id,
            'allocated_portions' => 150,
        ]);

        $this->batchA = FoodBatch::create([
            'production_plan_id' => $this->plan->id,
            'menu_id' => $this->menu->id,
            'batch_code' => 'BATCH-A-' . uniqid(),
            'portion_category' => 'STANDARD',
            'planned_portions' => 100,
            'actual_portions' => 100,
            'status' => FoodBatchStatus::RELEASED,
            'created_by' => $this->pic->id,
        ]);

        $this->batchB = FoodBatch::create([
            'production_plan_id' => $this->plan->id,
            'menu_id' => $this->menu->id,
            'batch_code' => 'BATCH-B-' . uniqid(),
            'portion_category' => 'STANDARD',
            'planned_portions' => 50,
            'actual_portions' => 50,
            'status' => FoodBatchStatus::RELEASED,
            'created_by' => $this->pic->id,
        ]);

        $this->itemA = DeliveryItem::create([
            'trip_stop_id' => $this->stop->id,
            'food_batch_id' => $this->batchA->id,
            'production_allocation_id' => $allocation->id,
            'planned_portions' => 100,
            'loaded_portions' => 100,
            'delivered_portions' => 0,
            'status' => 'LOADED',
        ]);

        $this->itemB = DeliveryItem::create([
            'trip_stop_id' => $this->stop->id,
            'food_batch_id' => $this->batchB->id,
            'production_allocation_id' => $allocation->id,
            'planned_portions' => 50,
            'loaded_portions' => 50,
            'delivered_portions' => 0,
            'status' => 'LOADED',
        ]);
    }

    /**
     * GATE 1 REPRODUCTION:
     * Batch A loaded 100, Batch B loaded 50.
     * Request sends items array containing ONLY Batch A (received 100, damaged 0).
     * Batch B is omitted.
     * Currently this causes an Undefined array key error / HTTP 500 when it should be validated cleanly.
     */
    public function test_gate_1_reproduce_partial_items_omitting_batch_b_causes_error_or_improper_processing(): void
    {
        $payload = [
            'received_portions' => 100,
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
            'notes' => 'Kekurangan fisik 50 porsi dari batch B yang tidak tiba.',
            'items' => [
                $this->itemA->id => [
                    'received_portions' => 100,
                    'damaged_portions' => 0,
                ],
                // Item B is completely omitted!
            ],
        ];

        // Hit the endpoint via PIC
        $response = $this->actingAs($this->pic)->postJson("/school/trip-stops/{$this->stop->id}/receipt", $payload);

        // GATE 1: Must be rejected with 422 validation error, NEVER HTTP 500!
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);
    }

    /**
     * GATE 2 HARDENED VERIFICATION:
     * Fictional FIFO provenance eliminated in multi-batch discrepancy without physical per-batch breakdown.
     * Batch A loaded 100, Batch B loaded 50. Expected = 150.
     * Total received = 100, shortage = 50. No items breakdown sent.
     * Verified: delivered_portions is NULL and status is UNRESOLVED_DISCREPANCY (no fictional FIFO allocation!).
     */
    public function test_gate_2_reproduce_fictional_fifo_provenance(): void
    {
        $payload = [
            'received_portions' => 100,
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
            'notes' => 'Kekurangan 50 porsi tanpa perincian batch.',
        ];

        $service = app(SchoolHandoverService::class);
        $service->confirmReceipt($this->stop, $this->pic, $payload);

        $this->itemA->refresh();
        $this->itemB->refresh();

        // GATE 2: Neither Item A nor Item B is assigned fictional delivered portions
        $this->assertNull($this->itemA->delivered_portions, 'Item A delivered portions must be null when attribution is unresolved');
        $this->assertNull($this->itemB->delivered_portions, 'Item B delivered portions must be null when attribution is unresolved');
        $this->assertSame('UNRESOLVED_DISCREPANCY', $this->itemA->status);
        $this->assertSame('UNRESOLVED_DISCREPANCY', $this->itemB->status);

        // QualityChecks must record null accepted and null rejected
        $qcA = QualityCheck::where('food_batch_id', $this->batchA->id)->latest()->first();
        $qcB = QualityCheck::where('food_batch_id', $this->batchB->id)->latest()->first();
        $this->assertNull($qcA->accepted_portions);
        $this->assertNull($qcA->rejected_portions);
        $this->assertNull($qcB->accepted_portions);
        $this->assertNull($qcB->rejected_portions);
    }

    /**
     * GATE 3 HARDENED VERIFICATION:
     * Batch A loaded 10. PIC reports Batch A received 40, damaged 30.
     * Batch B loaded 90. PIC reports Batch B received 60, damaged 0.
     * Total stop received = 100, damaged = 30.
     * Verified: QualityCheck strictly respects loaded bounds: accepted (0) + rejected (10) = 10 == loaded (10).
     */
    public function test_gate_3_reproduce_batch_a_loaded_10_received_40_anomaly(): void
    {
        $this->itemA->update(['loaded_portions' => 10]);
        $this->itemB->update(['loaded_portions' => 90]);
        $this->stop->update(['loaded_portions' => 100, 'planned_portions' => 100]);

        $payload = [
            'received_portions' => 100,
            'damaged_portions' => 30,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
            'notes' => 'Catatan anomali fisik muatan batch.',
            'items' => [
                $this->itemA->id => [
                    'received_portions' => 40,
                    'damaged_portions' => 30,
                ],
                $this->itemB->id => [
                    'received_portions' => 60,
                    'damaged_portions' => 0,
                ],
            ],
        ];

        $service = app(SchoolHandoverService::class);
        $service->confirmReceipt($this->stop, $this->pic, $payload);

        $qcA = QualityCheck::where('food_batch_id', $this->batchA->id)->latest()->first();
        $qcB = QualityCheck::where('food_batch_id', $this->batchB->id)->latest()->first();
        $this->itemA->refresh();
        $this->itemB->refresh();

        // GATE 3: Batch A loaded 10: accepted is 0, rejected is 10. Total evaluated = 10 == loaded (10)!
        $this->assertSame(0, $qcA->accepted_portions);
        $this->assertSame(10, $qcA->rejected_portions);
        $this->assertSame(10, $qcA->accepted_portions + $qcA->rejected_portions);
        $this->assertSame(10, $this->itemA->delivered_portions, 'Item A physical delivered is capped at loaded portions (10)');

        // Batch B loaded 90: received 60, damaged 0 -> accepted 60, rejected 0. Delivered 60.
        $this->assertSame(60, $qcB->accepted_portions);
        $this->assertSame(0, $qcB->rejected_portions);
        $this->assertSame(60, $this->itemB->delivered_portions);
    }

    /**
     * GATE 6: Evidence cleanup at the right failure point.
     * Ensures the file is ACTUALLY WRITTEN to disk first, then when transaction fails
     * (e.g. failure injected during subsequent ActivityLog creation), the newly written file
     * is deleted, no orphan EvidencePhoto exists, and existing files remain untouched.
     */
    public function test_gate_6_evidence_cleanup_after_file_is_stored_upon_transaction_failure(): void
    {
        Storage::fake('local');

        // Existing file from another successful transaction must stay intact
        $existingLegitPath = 'evidence_photos/receipts/existing_legit.jpg';
        Storage::disk('local')->put($existingLegitPath, 'existing valid file content');
        $this->assertTrue(Storage::disk('local')->exists($existingLegitPath));

        $file = UploadedFile::fake()->image('bukti_penerimaan.jpg', 600, 600);

        // Inject failure on ActivityLog creating event specifically for 'school_service.receipt_confirmed'
        // which executes immediately AFTER storeRecordEvidence has written the file to disk!
        $injectedRan = false;
        $capturedFileExistedBeforeRollback = false;
        ActivityLog::creating(function ($log) use (&$injectedRan, &$capturedFileExistedBeforeRollback) {
            if ($log->event === 'school_service.receipt_confirmed') {
                $injectedRan = true;
                $allFiles = Storage::disk('local')->allFiles('evidence_photos/receipts');
                $capturedFileExistedBeforeRollback = count($allFiles) > 1; // Existing legit + newly uploaded file
                throw new \RuntimeException('Injected failure after storeRecordEvidence in transaction');
            }
        });

        $payload = [
            'received_portions' => 150,
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
            'notes' => 'Penerimaan lengkap dengan bukti foto.',
            'evidence_photo' => $file,
        ];

        $service = app(SchoolHandoverService::class);

        try {
            $service->confirmReceipt($this->stop, $this->pic, $payload);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('Injected failure after storeRecordEvidence in transaction', $e->getMessage());
        }

        $this->assertTrue($injectedRan, 'Failure injection on ActivityLog must execute');
        $this->assertTrue($capturedFileExistedBeforeRollback, 'The uploaded file MUST have existed on disk prior to rollback');

        // Verify newly uploaded file is CLEANED UP from storage
        $remainingFiles = Storage::disk('local')->allFiles('evidence_photos/receipts');
        $this->assertCount(1, $remainingFiles, 'Only the pre-existing file should remain; orphan was deleted');
        $this->assertTrue(Storage::disk('local')->exists($existingLegitPath), 'Existing legit file remains intact');

        // Verify DB rollback: no SchoolServiceRecord and no EvidencePhoto
        $this->assertDatabaseMissing('school_service_records', [
            'trip_stop_id' => $this->stop->id,
        ]);
        $this->assertSame(0, EvidencePhoto::count(), 'Zero orphan EvidencePhoto records in database');
    }

    /**
     * GATE 7: Foreign item ID is rejected.
     */
    public function test_gate_7_foreign_item_id_is_rejected(): void
    {
        $payload = [
            'received_portions' => 150,
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
            'notes' => 'Percobaan mengirim foreign item id.',
            'items' => [
                $this->itemA->id => ['received_portions' => 100, 'damaged_portions' => 0],
                999999 => ['received_portions' => 50, 'damaged_portions' => 0], // Foreign/Nonexistent ID!
            ],
        ];

        $response = $this->actingAs($this->pic)->postJson("/school/trip-stops/{$this->stop->id}/receipt", $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);
    }

    /**
     * GATE 7: Mode B (AGGREGATE) rejects items payload.
     */
    public function test_gate_7_mode_b_aggregate_rejects_items(): void
    {
        $payload = [
            'verification_mode' => 'AGGREGATE',
            'received_portions' => 150,
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
            'notes' => 'Mode aggregate namun menyertakan items.',
            'items' => [
                $this->itemA->id => ['received_portions' => 100, 'damaged_portions' => 0],
                $this->itemB->id => ['received_portions' => 50, 'damaged_portions' => 0],
            ],
        ];

        $response = $this->actingAs($this->pic)->postJson("/school/trip-stops/{$this->stop->id}/receipt", $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);
    }

    /**
     * GATE 7: Over-receipt (received > expected) requires notes.
     */
    public function test_gate_7_over_receipt_overage_requires_notes(): void
    {
        // 1. Without notes: must fail
        $payloadWithoutNotes = [
            'received_portions' => 160, // 10 overage
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
            'notes' => '', // Empty!
        ];

        $response = $this->actingAs($this->pic)->postJson("/school/trip-stops/{$this->stop->id}/receipt", $payloadWithoutNotes);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['notes']);

        // 2. With notes: accepted
        $payloadWithNotes = array_merge($payloadWithoutNotes, [
            'notes' => 'Kelebihan fisik 10 ompreng di atas manifest muatan SPPG.',
        ]);
        $responseSuccess = $this->actingAs($this->pic)->postJson("/school/trip-stops/{$this->stop->id}/receipt", $payloadWithNotes);
        $responseSuccess->assertOk();
        $this->assertTrue($responseSuccess->json('success'));
    }

    /**
     * GATE 7: HELD receipt sets stop to EXCEPTION and does NOT activate next stop.
     */
    public function test_gate_7_held_receipt_sets_stop_exception_and_does_not_activate_next_stop(): void
    {
        $school2 = School::where('school_code', 'SCH-SMPN-02')->firstOrFail();
        $nextStop = TripStop::create([
            'trip_id' => $this->trip->id,
            'school_id' => $school2->id,
            'stop_type' => 'DELIVERY',
            'sequence_number' => 2,
            'planned_portions' => 100,
            'loaded_portions' => 100,
            'status' => TripStopStatus::PENDING,
        ]);

        $payload = [
            'received_portions' => 150,
            'damaged_portions' => 0,
            'appearance_ok' => false,
            'aroma_ok' => false,
            'texture_ok' => false,
            'taste_tested' => false,
            'taste_skipped_reason' => 'Makanan berbau asam menyengat, tidak aman dicicipi.',
            'conclusion' => 'UNSAFE',
            'notes' => 'Makanan rusak dan berbau busuk. Distribusi ditahan.',
        ];

        $service = app(SchoolHandoverService::class);
        $record = $service->confirmReceipt($this->stop, $this->pic, $payload);

        $this->stop->refresh();
        $nextStop->refresh();

        $this->assertSame(SchoolReceiptStatus::HELD, $record->status);
        $this->assertSame(TripStopStatus::EXCEPTION, $this->stop->status);
        $this->assertSame(TripStopStatus::PENDING, $nextStop->status, 'Next stop must remain PENDING when delivery is HELD');
    }

    /**
     * GATE 7: Foreign PIC cannot confirm receipt for another school (tenant isolation).
     */
    public function test_gate_7_foreign_pic_is_rejected(): void
    {
        $foreignPic = User::where('email', 'pic.smpn02@mbgtrack.test')->firstOrFail();

        $payload = [
            'received_portions' => 150,
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
            'notes' => 'Percobaan akses foreign PIC.',
        ];

        $response = $this->actingAs($foreignPic)->postJson("/school/trip-stops/{$this->stop->id}/receipt", $payload);
        $this->assertTrue(in_array($response->status(), [403, 422], true));
    }

    /**
     * GATE 7: Duplicate request (idempotency) returns existing record unchanged.
     */
    public function test_gate_7_idempotent_receipt_returns_existing_record_unchanged(): void
    {
        $payload = [
            'received_portions' => 150,
            'damaged_portions' => 0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => 'SAFE',
            'notes' => 'Penerimaan pertama.',
        ];

        $service = app(SchoolHandoverService::class);
        $record1 = $service->confirmReceipt($this->stop, $this->pic, $payload);

        // Submit second time with different notes
        $payload2 = array_merge($payload, ['notes' => 'Percobaan modifikasi idempotency']);
        $record2 = $service->confirmReceipt($this->stop, $this->pic, $payload2);

        $this->assertSame($record1->id, $record2->id);
        $this->assertSame('Penerimaan pertama.', $record2->notes);
    }
}

