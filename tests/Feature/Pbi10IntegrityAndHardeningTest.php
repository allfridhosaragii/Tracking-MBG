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
use Carbon\Carbon;
use Database\Seeders\MbgTrackBaselineSeeder;
use Database\Seeders\MenuBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Pbi10IntegrityAndHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected Sppg $sppg;
    protected School $schoolA;
    protected School $schoolB;
    protected User $driver;
    protected User $otherDriver;
    protected User $picSchoolA;
    protected User $picSchoolB;
    protected Vehicle $vehicle;
    protected Menu $menu;
    protected ProductionPlan $plan;
    protected FoodBatch $batchA;
    protected FoodBatch $batchB;
    protected FoodBatch $batchC;
    protected Trip $trip;
    protected TripStop $stop1;
    protected TripStop $stop2;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('private');

        $this->seed(MbgTrackBaselineSeeder::class);
        $this->seed(MenuBaselineSeeder::class);

        $this->sppg = Sppg::where('code', 'SPPG-SUB-01')->firstOrFail();
        $this->schoolA = School::where('school_code', 'SCH-SDN-01')->firstOrFail();
        $this->schoolB = School::where('school_code', 'SCH-SMPN-02')->firstOrFail();

        $this->driver = User::where('email', 'kurir@mbgtrack.test')->firstOrFail();
        $this->otherDriver = User::create([
            'sppg_id' => $this->sppg->id,
            'name' => 'Kurir Cadangan',
            'email' => 'kurir2@mbgtrack.test',
            'password' => bcrypt('password'),
            'role' => UserRole::KURIR,
            'is_active' => true,
        ]);

        $this->picSchoolA = User::where('email', 'pic.sdn01@mbgtrack.test')->firstOrFail();
        $this->picSchoolB = User::where('email', 'pic.smpn02@mbgtrack.test')->firstOrFail();

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

        $this->batchA = FoodBatch::create([
            'production_plan_id' => $this->plan->id,
            'menu_id' => $this->menu->id,
            'batch_code' => 'BATCH-A-' . uniqid(),
            'planned_portions' => 500,
            'actual_portions' => 500,
            'status' => FoodBatchStatus::RELEASED,
            'created_by' => User::where('role', UserRole::ASISTEN_LAPANGAN)->first()->id,
        ]);

        $this->batchB = FoodBatch::create([
            'production_plan_id' => $this->plan->id,
            'menu_id' => $this->menu->id,
            'batch_code' => 'BATCH-B-' . uniqid(),
            'planned_portions' => 300,
            'actual_portions' => 300,
            'status' => FoodBatchStatus::RELEASED,
            'created_by' => User::where('role', UserRole::ASISTEN_LAPANGAN)->first()->id,
        ]);

        $this->batchC = FoodBatch::create([
            'production_plan_id' => $this->plan->id,
            'menu_id' => $this->menu->id,
            'batch_code' => 'BATCH-C-' . uniqid(),
            'planned_portions' => 200,
            'actual_portions' => 200,
            'status' => FoodBatchStatus::RELEASED,
            'created_by' => User::where('role', UserRole::ASISTEN_LAPANGAN)->first()->id,
        ]);

        $this->trip = Trip::create([
            'sppg_id' => $this->sppg->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'service_date' => Carbon::today(),
            'trip_type' => TripType::DELIVERY,
            'status' => TripStatus::IN_PROGRESS,
            'created_by' => User::where('role', UserRole::KEPALA_SPPG)->first()->id,
            'trip_code' => 'TRIP-REG-' . uniqid(),
            'started_at' => Carbon::now()->subMinutes(30),
            'tracking_status' => 'ONLINE',
            'active_tracking_session_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $this->stop1 = TripStop::create([
            'trip_id' => $this->trip->id,
            'school_id' => $this->schoolA->id,
            'sequence_number' => 1,
            'status' => TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
            'planned_portions' => 300,
            'loaded_portions' => 300,
            'arrived_at' => Carbon::now()->subMinutes(10),
        ]);

        $this->stop2 = TripStop::create([
            'trip_id' => $this->trip->id,
            'school_id' => $this->schoolB->id,
            'sequence_number' => 2,
            'status' => TripStopStatus::PENDING,
            'planned_portions' => 200,
            'loaded_portions' => 200,
        ]);
    }

    /**
     * Helper to setup allocations and delivery items.
     */
    protected function setupAllocationAndItem(TripStop $stop, FoodBatch $batch, int $portions): DeliveryItem
    {
        $req = DailyPortionRequest::firstOrCreate(
            ['school_id' => $stop->school_id, 'service_date' => Carbon::today()],
            [
                'sppg_id' => $this->sppg->id,
                'registered_count' => $portions,
                'requested_portions' => $portions,
                'status' => \App\Enums\PortionRequestStatus::CONFIRMED,
                'created_by' => $this->picSchoolA->id,
                'confirmed_at' => now(),
            ]
        );

        $alloc = ProductionAllocation::firstOrCreate(
            [
                'production_plan_id' => $this->plan->id,
                'portion_request_id' => $req->id,
            ],
            [
                'school_id' => $stop->school_id,
                'allocated_portions' => 500,
            ]
        );

        return DeliveryItem::create([
            'trip_stop_id' => $stop->id,
            'food_batch_id' => $batch->id,
            'production_allocation_id' => $alloc->id,
            'planned_portions' => $portions,
            'loaded_portions' => $portions,
        ]);
    }

    /**
     * GATE A: Multi-batch portion integrity (2 batches in 1 stop).
     * Stop has Batch A (180 portions) and Batch B (120 portions), Total loaded = 300.
     * When PIC receives 300, delivered_portions must NOT be 300 on both items (total 600).
     * It must sum to exactly 300.
     */
    public function test_gate_a_two_batches_one_stop_portion_integrity(): void
    {
        $itemA = $this->setupAllocationAndItem($this->stop1, $this->batchA, 180);
        $itemB = $this->setupAllocationAndItem($this->stop1, $this->batchB, 120);

        $this->stop1->update(['loaded_portions' => 300, 'planned_portions' => 300]);

        $response = $this->actingAs($this->picSchoolA)->post(route('school.receipts.confirm', $this->stop1), [
            'received_portions' => 300,
            'damaged_portions' => 0,
            'appearance_ok' => 1,
            'aroma_ok' => 1,
            'texture_ok' => 1,
            'taste_tested' => 1,
            'taste_ok' => 1,
            'conclusion' => 'SAFE',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('school.receipts.show', $this->stop1));

        $itemA->refresh();
        $itemB->refresh();

        // Invariant: Total delivered strictly equals received portions (300)
        $totalDelivered = $itemA->delivered_portions + $itemB->delivered_portions;
        $this->assertEquals(300, $totalDelivered, "Total delivered across items must equal 300, not duplicate count!");
        $this->assertLessThanOrEqual(180, $itemA->delivered_portions, "Item A cannot exceed loaded");
        $this->assertLessThanOrEqual(120, $itemB->delivered_portions, "Item B cannot exceed loaded");
        $this->assertNotEquals(600, $totalDelivered, "Fatal: Multi-batch duplicate counting detected!");
    }

    /**
     * GATE A: Three batches in one stop with shortage.
     * Batch A: 100, Batch B: 150, Batch C: 50. Total loaded = 300.
     * PIC receives 280 (shortage 20).
     * Total delivered across all 3 items must strictly equal 280.
     */
    public function test_gate_a_three_batches_one_stop_portions_invariant(): void
    {
        $itemA = $this->setupAllocationAndItem($this->stop1, $this->batchA, 100);
        $itemB = $this->setupAllocationAndItem($this->stop1, $this->batchB, 150);
        $itemC = $this->setupAllocationAndItem($this->stop1, $this->batchC, 50);

        $this->stop1->update(['loaded_portions' => 300, 'planned_portions' => 300]);

        $response = $this->actingAs($this->picSchoolA)->post(route('school.receipts.confirm', $this->stop1), [
            'received_portions' => 280,
            'damaged_portions' => 0,
            'appearance_ok' => 1,
            'aroma_ok' => 1,
            'texture_ok' => 1,
            'taste_tested' => 1,
            'taste_ok' => 1,
            'conclusion' => 'SAFE',
            'notes' => 'Kekurangan 20 porsi dari SPPG.',
        ]);

        $response->assertSessionHasNoErrors();

        $itemA->refresh();
        $itemB->refresh();
        $itemC->refresh();

        // GATE 2: Multi-batch shortage without verified per-batch breakdown leaves delivered_portions null
        $this->assertNull($itemA->delivered_portions, "Item A delivered is null when attribution unresolved");
        $this->assertNull($itemB->delivered_portions, "Item B delivered is null when attribution unresolved");
        $this->assertNull($itemC->delivered_portions, "Item C delivered is null when attribution unresolved");
        $this->assertSame('UNRESOLVED_DISCREPANCY', $itemA->status);
        $this->assertSame('UNRESOLVED_DISCREPANCY', $itemB->status);
        $this->assertSame('UNRESOLVED_DISCREPANCY', $itemC->status);
    }

    /**
     * GATE B: Zero loaded portions must NOT fallback to planned_portions; must fail-safe.
     */
    public function test_gate_b_zero_loaded_portions_fails_safe_without_fallback_to_planned(): void
    {
        // Stop has planned = 300, but loaded_portions = 0
        $this->stop1->update(['loaded_portions' => 0, 'planned_portions' => 300]);

        // Attempting to confirm receipt on an empty/unloaded stop must throw validation error
        $response = $this->actingAs($this->picSchoolA)->post(route('school.receipts.confirm', $this->stop1), [
            'received_portions' => 300,
            'damaged_portions' => 0,
            'appearance_ok' => 1,
            'aroma_ok' => 1,
            'texture_ok' => 1,
            'taste_tested' => 1,
            'taste_ok' => 1,
            'conclusion' => 'SAFE',
        ]);

        $response->assertSessionHasErrors(['loaded_portions']);
    }

    /**
     * GATE C: Over-receipt must be flagged with discrepancy and requires explanation notes.
     * Expected: 100, Received: 110.
     */
    public function test_gate_c_over_receipt_is_flagged_with_discrepancy(): void
    {
        $this->setupAllocationAndItem($this->stop1, $this->batchA, 100);
        $this->stop1->update(['loaded_portions' => 100, 'planned_portions' => 100]);

        $response = $this->actingAs($this->picSchoolA)->post(route('school.receipts.confirm', $this->stop1), [
            'received_portions' => 110,
            'damaged_portions' => 0,
            'appearance_ok' => 1,
            'aroma_ok' => 1,
            'texture_ok' => 1,
            'taste_tested' => 1,
            'taste_ok' => 1,
            'conclusion' => 'SAFE',
            'notes' => 'Ada kelebihan 10 porsi yang disertakan kurir.',
        ]);

        $response->assertSessionHasNoErrors();

        $record = SchoolServiceRecord::where('trip_stop_id', $this->stop1->id)->firstOrFail();
        $this->assertEquals(SchoolReceiptStatus::RECEIVED_WITH_DISCREPANCY, $record->status, 'Over-receipt must be flagged as RECEIVED_WITH_DISCREPANCY!');
        $this->assertEquals(110, $record->received_portions);
    }

    /**
     * GATE D: Food safety — Negative taste (taste_ok = false) CANNOT be overridden to SAFE.
     */
    public function test_gate_d_negative_taste_cannot_be_overridden_to_safe(): void
    {
        $this->setupAllocationAndItem($this->stop1, $this->batchA, 100);
        $this->stop1->update(['loaded_portions' => 100]);

        $response = $this->actingAs($this->picSchoolA)->post(route('school.receipts.confirm', $this->stop1), [
            'received_portions' => 100,
            'damaged_portions' => 0,
            'appearance_ok' => 1,
            'aroma_ok' => 1,
            'texture_ok' => 1,
            'taste_tested' => 1,
            'taste_ok' => 0, // Taste check failed!
            'conclusion' => 'SAFE', // Attempting to force SAFE!
        ]);

        $response->assertSessionHasErrors(['conclusion']);
    }

    /**
     * GATE D: Food safety portions calculation:
     * Received 100, Damaged 10 -> QualityCheck accepted_portions = 90, rejected_portions = 10.
     */
    public function test_gate_d_qc_portions_calculation_is_consistent(): void
    {
        $this->setupAllocationAndItem($this->stop1, $this->batchA, 100);
        $this->stop1->update(['loaded_portions' => 100]);

        $response = $this->actingAs($this->picSchoolA)->post(route('school.receipts.confirm', $this->stop1), [
            'received_portions' => 100,
            'damaged_portions' => 10,
            'appearance_ok' => 1,
            'aroma_ok' => 1,
            'texture_ok' => 1,
            'taste_tested' => 1,
            'taste_ok' => 1,
            'conclusion' => 'SAFE',
            'notes' => '10 porsi tumpah saat penyerahan.',
        ]);

        $response->assertSessionHasNoErrors();

        $qc = QualityCheck::where('trip_stop_id', $this->stop1->id)
            ->where('check_stage', QualityCheckStage::ON_ARRIVAL)
            ->firstOrFail();

        $this->assertEquals(90, $qc->accepted_portions, 'Accepted portions must be received - damaged');
        $this->assertEquals(10, $qc->rejected_portions, 'Rejected portions must be damaged portions');
    }

    /**
     * GATE E: Quality check batch provenance:
     * Never fall back to batch from another stop if stop has no items.
     */
    public function test_gate_e_qc_batch_provenance_never_falls_back_to_foreign_stop_batch(): void
    {
        // Stop 1 has Batch A
        $this->setupAllocationAndItem($this->stop1, $this->batchA, 100);

        // Stop 2 has NO items
        $this->stop2->update([
            'status' => TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
            'arrived_at' => now(),
            'loaded_portions' => 0,
        ]);

        $response = $this->actingAs($this->picSchoolB)->post(route('school.receipts.confirm', $this->stop2), [
            'received_portions' => 100,
            'damaged_portions' => 0,
            'appearance_ok' => 1,
            'aroma_ok' => 1,
            'texture_ok' => 1,
            'taste_tested' => 1,
            'taste_ok' => 1,
            'conclusion' => 'SAFE',
        ]);

        // Must reject because stop has no valid manifest / items
        $response->assertSessionHasErrors();

        // Must NOT create a QC for Stop 2 claiming it received Batch A
        $qcForeign = QualityCheck::where('trip_stop_id', $this->stop2->id)->first();
        $this->assertNull($qcForeign, 'Must not fabricate QC using batch from another stop!');
    }

    /**
     * GATE F: Evidence photo authorization:
     * PIC School A cannot view School B photo (HTTP 403).
     * PIC School A can view School A photo (HTTP 200).
     * Unassigned driver cannot view photo (HTTP 403).
     * Guest cannot view photo (HTTP 401/403).
     */
    public function test_gate_f_evidence_photo_authorization(): void
    {
        $fileA = UploadedFile::fake()->image('photo_school_a.jpg', 600, 400);
        $pathA = $fileA->store('evidence_photos/stops', 'local');

        $photoA = EvidencePhoto::create([
            'evidenceable_type' => TripStop::class,
            'evidenceable_id' => $this->stop1->id,
            'evidence_type' => 'SCHOOL_HANDOVER',
            'uploaded_by' => $this->driver->id,
            'file_path' => $pathA,
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'captured_at' => now(),
        ]);

        $fileB = UploadedFile::fake()->image('photo_school_b.jpg', 600, 400);
        $pathB = $fileB->store('evidence_photos/stops', 'local');

        $photoB = EvidencePhoto::create([
            'evidenceable_type' => TripStop::class,
            'evidenceable_id' => $this->stop2->id,
            'evidence_type' => 'SCHOOL_HANDOVER',
            'uploaded_by' => $this->driver->id,
            'file_path' => $pathB,
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'captured_at' => now(),
        ]);

        // 1. PIC School A can view Photo A
        $this->actingAs($this->picSchoolA)->get(route('evidence-photos.show', $photoA))->assertOk();

        // 2. PIC School A CANNOT view Photo B (School B) -> 403
        $this->actingAs($this->picSchoolA)->get(route('evidence-photos.show', $photoB))->assertStatus(403);

        // 3. Unassigned Courier CANNOT view Photo A -> 403
        $this->actingAs($this->otherDriver)->get(route('evidence-photos.show', $photoA))->assertStatus(403);

        // 4. Guest / Public CANNOT view photo -> 401/302 to login
        auth()->logout();
        $this->get(route('evidence-photos.show', $photoA))->assertRedirect(route('login'));
    }

    /**
     * GATE G: SchoolServiceRecord::isConfirmed() helper across all enum statuses.
     */
    public function test_gate_g_school_service_record_is_confirmed(): void
    {
        $record = new SchoolServiceRecord();

        $record->status = SchoolReceiptStatus::WAITING;
        $this->assertFalse($record->isConfirmed());

        $record->status = SchoolReceiptStatus::RECEIVED;
        $this->assertTrue($record->isConfirmed());

        $record->status = SchoolReceiptStatus::RECEIVED_WITH_DISCREPANCY;
        $this->assertTrue($record->isConfirmed());

        $record->status = SchoolReceiptStatus::HELD;
        $this->assertTrue($record->isConfirmed());

        $record->status = SchoolReceiptStatus::REJECTED;
        $this->assertTrue($record->isConfirmed());
    }

    /**
     * GATE H: Read vs Write permissions:
     * PIC can view receipt history when status is HELD or EXCEPTION.
     * But cannot re-submit / edit confirmed receipt.
     */
    public function test_gate_h_read_vs_write_permissions(): void
    {
        $this->setupAllocationAndItem($this->stop1, $this->batchA, 300);

        // 1. Mark as HELD & EXCEPTION
        $record = SchoolServiceRecord::create([
            'trip_stop_id' => $this->stop1->id,
            'expected_portions' => 300,
            'received_portions' => 300,
            'damaged_portions' => 0,
            'shortage_portions' => 0,
            'received_by' => $this->picSchoolA->id,
            'received_at' => now(),
            'status' => SchoolReceiptStatus::HELD,
        ]);
        $this->stop1->update(['status' => TripStopStatus::EXCEPTION]);

        // PIC must be able to view history without 403!
        $this->actingAs($this->picSchoolA)
            ->get(route('school.receipts.show', $this->stop1))
            ->assertOk();

        // But attempting to re-confirm / edit the confirmed receipt must be denied!
        $this->actingAs($this->picSchoolA)
            ->post(route('school.receipts.confirm', $this->stop1), [
                'received_portions' => 300,
                'damaged_portions' => 0,
                'appearance_ok' => 1,
                'aroma_ok' => 1,
                'texture_ok' => 1,
                'taste_tested' => 1,
                'taste_ok' => 1,
                'conclusion' => 'SAFE',
            ])
            ->assertStatus(403);
    }

    /**
     * GATE I: Multi-stop route consistency:
     * Handover of the last stop does NOT prematurely mark the entire trip as COMPLETED (PBI-11 return process).
     */
    public function test_gate_i_last_stop_handover_does_not_prematurely_complete_trip(): void
    {
        // Setup stop 1 already completed
        $this->stop1->update(['status' => TripStopStatus::HANDED_OVER]);

        // Stop 2 arrives
        $this->setupAllocationAndItem($this->stop2, $this->batchB, 200);
        $this->stop2->update([
            'status' => TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
            'arrived_at' => now(),
            'loaded_portions' => 200,
        ]);

        $this->actingAs($this->picSchoolB)->post(route('school.receipts.confirm', $this->stop2), [
            'received_portions' => 200,
            'damaged_portions' => 0,
            'appearance_ok' => 1,
            'aroma_ok' => 1,
            'texture_ok' => 1,
            'taste_tested' => 1,
            'taste_ok' => 1,
            'conclusion' => 'SAFE',
        ]);

        $this->stop2->refresh();
        $this->assertEquals(TripStopStatus::HANDED_OVER, $this->stop2->status);

        $this->trip->refresh();
        $this->assertEquals(TripStatus::IN_PROGRESS, $this->trip->status, 'Trip must remain IN_PROGRESS until PBI-11 return & final wrapup!');
    }

    /**
     * GATE B: Loaded null fails safe without fallback to planned.
     */
    public function test_gate_b_loaded_null_fails_safe(): void
    {
        $this->stop1->update(['loaded_portions' => null, 'planned_portions' => 300]);

        $response = $this->actingAs($this->picSchoolA)->post(route('school.receipts.confirm', $this->stop1), [
            'received_portions' => 300,
            'damaged_portions' => 0,
            'appearance_ok' => 1,
            'aroma_ok' => 1,
            'texture_ok' => 1,
            'taste_tested' => 1,
            'taste_ok' => 1,
            'conclusion' => 'SAFE',
        ]);

        $response->assertSessionHasErrors(['loaded_portions']);
    }

    /**
     * GATE D: Spoiled food cannot be tasted and must safely skip taste test.
     */
    public function test_gate_d_spoiled_food_cannot_be_tasted_and_skips_safely(): void
    {
        $this->setupAllocationAndItem($this->stop1, $this->batchA, 100);
        $this->stop1->update(['loaded_portions' => 100]);

        // Attempting to taste spoiled food (appearance_ok = 0 and aroma_ok = 0 with taste_tested = 1 and taste_ok = 1) is rejected!
        $resForbidden = $this->actingAs($this->picSchoolA)->post(route('school.receipts.confirm', $this->stop1), [
            'received_portions' => 100,
            'damaged_portions' => 0,
            'appearance_ok' => 0,
            'aroma_ok' => 0,
            'texture_ok' => 0,
            'taste_tested' => 1,
            'taste_ok' => 1,
            'conclusion' => 'SAFE',
        ]);
        $resForbidden->assertSessionHasErrors();

        // Safe skip with reason succeeds and marks HELD
        $resSafeSkip = $this->actingAs($this->picSchoolA)->post(route('school.receipts.confirm', $this->stop1), [
            'received_portions' => 100,
            'damaged_portions' => 0,
            'appearance_ok' => 0,
            'aroma_ok' => 0,
            'texture_ok' => 0,
            'taste_tested' => 0,
            'taste_skipped_reason' => 'Makanan berlendir dan berbau busuk; tasting dilewati demi keselamatan.',
            'conclusion' => 'UNSAFE',
            'notes' => 'Makanan ditolak saat kedatangan.',
        ]);
        $resSafeSkip->assertSessionHasNoErrors();

        $record = SchoolServiceRecord::where('trip_stop_id', $this->stop1->id)->firstOrFail();
        $this->assertEquals(SchoolReceiptStatus::HELD, $record->status);
    }

    /**
     * GATE H: Reconfirmation attempt does not alter verified receipt values.
     */
    public function test_gate_h_reconfirmation_does_not_alter_original_receipt_values(): void
    {
        $this->setupAllocationAndItem($this->stop1, $this->batchA, 300);
        $this->stop1->update(['loaded_portions' => 300]);

        // Initial confirmation: 300 received, 0 damaged
        $this->actingAs($this->picSchoolA)->post(route('school.receipts.confirm', $this->stop1), [
            'received_portions' => 300,
            'damaged_portions' => 0,
            'appearance_ok' => 1,
            'aroma_ok' => 1,
            'texture_ok' => 1,
            'taste_tested' => 1,
            'taste_ok' => 1,
            'conclusion' => 'SAFE',
        ]);

        $record = SchoolServiceRecord::where('trip_stop_id', $this->stop1->id)->firstOrFail();
        $this->assertEquals(300, $record->received_portions);
        $this->assertEquals(0, $record->damaged_portions);

        // Attempt second submit with tampered numbers (e.g. 250 received, 50 damaged)
        $this->actingAs($this->picSchoolA)->post(route('school.receipts.confirm', $this->stop1), [
            'received_portions' => 250,
            'damaged_portions' => 50,
            'appearance_ok' => 1,
            'aroma_ok' => 1,
            'texture_ok' => 1,
            'taste_tested' => 1,
            'taste_ok' => 1,
            'conclusion' => 'SAFE',
        ]);

        $record->refresh();
        $this->assertEquals(300, $record->received_portions, 'Original received portions must remain immutable!');
        $this->assertEquals(0, $record->damaged_portions, 'Original damaged portions must remain immutable!');
    }
}

