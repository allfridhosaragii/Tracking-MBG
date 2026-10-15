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

class Pbi10MealDistributionComplianceTest extends TestCase
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
    protected FoodBatch $batch;
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

        $this->plan = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->menu->id,
            'service_date' => Carbon::today(),
            'target_portions' => 800,
            'status' => \App\Enums\ProductionPlanStatus::IN_PRODUCTION,
            'created_by' => User::where('role', UserRole::KEPALA_SPPG)->first()->id,
        ]);

        $this->batch = FoodBatch::create([
            'production_plan_id' => $this->plan->id,
            'menu_id' => $this->menu->id,
            'batch_code' => 'BATCH-TEST-' . uniqid(),
            'planned_portions' => 800,
            'actual_portions' => 800,
            'status' => FoodBatchStatus::RELEASED,
            'created_by' => User::where('role', UserRole::ASISTEN_LAPANGAN)->first()->id,
        ]);

        $this->trip = Trip::create([
            'trip_code' => 'TRIP-TEST-' . uniqid(),
            'sppg_id' => $this->sppg->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'trip_type' => TripType::DELIVERY,
            'status' => TripStatus::IN_PROGRESS,
            'service_date' => Carbon::today(),
            'started_at' => Carbon::now()->subMinutes(60),
            'vehicle_checked' => true,
            'vehicle_checked_at' => Carbon::now()->subMinutes(70),
            'total_planned_portions' => 800,
            'total_loaded_portions' => 800,
            'created_by' => User::where('role', UserRole::KEPALA_SPPG)->first()->id,
        ]);

        $this->stopA = TripStop::create([
            'trip_id' => $this->trip->id,
            'school_id' => $this->schoolA->id,
            'sequence_number' => 1,
            'planned_portions' => 340,
            'loaded_portions' => 340,
            'status' => TripStopStatus::ARRIVED,
            'arrived_at' => Carbon::now()->subMinutes(30),
        ]);

        $req1 = \App\Models\DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'sppg_id' => $this->sppg->id,
            'service_date' => Carbon::today(),
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => \App\Enums\PortionRequestStatus::CONFIRMED,
            'created_by' => $this->picSchoolA->id,
            'confirmed_at' => now(),
        ]);

        $alloc1 = \App\Models\ProductionAllocation::create([
            'production_plan_id' => $this->plan->id,
            'portion_request_id' => $req1->id,
            'school_id' => $this->schoolA->id,
            'allocated_portions' => 340,
        ]);

        DeliveryItem::create([
            'trip_stop_id' => $this->stopA->id,
            'food_batch_id' => $this->batch->id,
            'production_allocation_id' => $alloc1->id,
            'planned_portions' => 340,
            'loaded_portions' => 340,
            'status' => 'LOADED',
        ]);

        $this->stopB = TripStop::create([
            'trip_id' => $this->trip->id,
            'school_id' => $this->schoolB->id,
            'sequence_number' => 2,
            'planned_portions' => 460,
            'loaded_portions' => 460,
            'status' => TripStopStatus::PENDING,
        ]);

        $req2 = \App\Models\DailyPortionRequest::create([
            'school_id' => $this->schoolB->id,
            'sppg_id' => $this->sppg->id,
            'service_date' => Carbon::today(),
            'registered_count' => 460,
            'requested_portions' => 460,
            'status' => \App\Enums\PortionRequestStatus::CONFIRMED,
            'created_by' => $this->picSchoolB->id,
            'confirmed_at' => now(),
        ]);

        $alloc2 = \App\Models\ProductionAllocation::create([
            'production_plan_id' => $this->plan->id,
            'portion_request_id' => $req2->id,
            'school_id' => $this->schoolB->id,
            'allocated_portions' => 460,
        ]);

        DeliveryItem::create([
            'trip_stop_id' => $this->stopB->id,
            'food_batch_id' => $this->batch->id,
            'production_allocation_id' => $alloc2->id,
            'planned_portions' => 460,
            'loaded_portions' => 460,
            'status' => 'LOADED',
        ]);
    }

    /**
     * Test full happy path:
     * 340 received -> 2nd QC SAFE -> CLEARED_TO_SERVE -> DISTRIBUTED -> 338 served + 2 leftover -> MEAL_COMPLETED -> Tray READY_FOR_PICKUP.
     */
    public function test_full_workflow_from_receipt_to_2nd_qc_distribution_meal_completed_and_tray_ready(): void
    {
        $handoverService = app(SchoolHandoverService::class);

        // 1. Receipt confirmation on arrival
        $record = $handoverService->confirmReceipt(
            $this->stopA,
            $this->picSchoolA,
            [
                'received_portions' => 340,
                'damaged_portions' => 0,
                'appearance_ok' => true,
                'aroma_ok' => true,
                'texture_ok' => true,
                'taste_tested' => true,
                'taste_ok' => true,
                'temperature_c' => 65.0,
                'conclusion' => 'SAFE',
            ]
        );

        $this->assertEquals(SchoolReceiptStatus::RECEIVED, $record->status);
        $this->assertEquals(340, $record->received_portions);
        $this->assertEquals(0, $record->damaged_portions);

        // 2. Perform distinct second QC (BEFORE_CONSUMPTION)
        $responseQc = $this->actingAs($this->picSchoolA)
            ->post(route('school.meal-distribution.pre-consumption-check', $record), [
                'appearance_ok' => 1,
                'aroma_ok' => 1,
                'texture_ok' => 1,
                'taste_tested' => 1,
                'taste_ok' => 1,
                'temperature_c' => 58.0,
                'conclusion' => 'SAFE',
                'notes' => 'Pemeriksaan sebelum makan kedua lolos standar.',
            ]);

        $responseQc->assertRedirect(route('school.meal-distribution.show', $record));

        $record->refresh();
        $this->assertEquals(SchoolReceiptStatus::CLEARED_TO_SERVE, $record->status);

        // Check distinct QualityCheck record created with BEFORE_CONSUMPTION
        $secondQc = QualityCheck::where('trip_stop_id', $this->stopA->id)
            ->whereIn('check_stage', [QualityCheckStage::BEFORE_CONSUMPTION, QualityCheckStage::PRE_CONSUMPTION])
            ->first();
        $this->assertNotNull($secondQc);
        $this->assertEquals(QualityCheckConclusion::SAFE, $secondQc->conclusion);
        $this->assertEquals($this->picSchoolA->id, $secondQc->checked_by);

        // 3. Start distribution
        $responseStart = $this->actingAs($this->picSchoolA)
            ->post(route('school.meal-distribution.start', $record));

        $responseStart->assertRedirect(route('school.meal-distribution.show', $record));

        $record->refresh();
        $this->assertEquals(SchoolReceiptStatus::DISTRIBUTED, $record->status);
        $this->assertNotNull($record->distribution_started_at);

        // 4. Complete meal with 338 served + 2 leftover (sum 340 <= 340 usable)
        $responseComplete = $this->actingAs($this->picSchoolA)
            ->post(route('school.meal-distribution.complete', $record), [
                'served_portions' => 338,
                'leftover_portions' => 2,
                'notes' => '2 porsi tersisa karena siswa izin sakit.',
            ]);

        $responseComplete->assertRedirect(route('school.meal-distribution.show', $record));

        $record->refresh();
        $this->assertEquals(SchoolReceiptStatus::MEAL_COMPLETED, $record->status);
        $this->assertEquals(338, $record->served_portions);
        $this->assertEquals(2, $record->leftover_portions);
        $this->assertNotNull($record->meal_completed_at);

        // 5. Tray return can now transition to READY_FOR_PICKUP
        $trayReturn = $record->trayReturn;
        $this->assertNotNull($trayReturn);
        $this->assertEquals(TrayReturnStatus::IN_USE, $trayReturn->status);

        $responseTray = $this->actingAs($this->picSchoolA)
            ->post(route('school.trays.ready', $trayReturn), [
                'ready_count' => 15,
                'damaged_count' => 0,
                'notes' => '15 ompreng bersih siap jemput.',
            ]);

        $responseTray->assertRedirect(route('school.trays.index'));
        $trayReturn->refresh();
        $this->assertEquals(TrayReturnStatus::READY_FOR_PICKUP, $trayReturn->status);
        $this->assertEquals(15, $trayReturn->ready_count);
    }

    /**
     * Test Gate B: Trays CANNOT be marked ready before MEAL_COMPLETED without exception.
     */
    public function test_cannot_mark_trays_ready_before_meal_completed_without_exception(): void
    {
        $handoverService = app(SchoolHandoverService::class);
        $trayService = app(TrayReturnService::class);

        $record = $handoverService->confirmReceipt(
            $this->stopA,
            $this->picSchoolA,
            [
                'received_portions' => 340,
                'damaged_portions' => 0,
                'appearance_ok' => true,
                'aroma_ok' => true,
                'texture_ok' => true,
                'conclusion' => 'SAFE',
            ]
        );

        $trayReturn = $record->trayReturn;
        $this->assertNotNull($trayReturn);

        // Status is still RECEIVED -> markReady must fail
        $this->expectException(\Symfony\Component\HttpKernel\Exception\ConflictHttpException::class);
        $this->expectExceptionMessage('kegiatan makan belum selesai (MEAL_COMPLETED)');

        $trayService->markReady($trayReturn, $this->picSchoolA, [
            'ready_count' => 15,
        ]);
    }

    /**
     * Test 2nd QC failure transitions to HELD and creates a high-severity FOOD_SAFETY incident.
     */
    public function test_2nd_qc_failure_transitions_to_held_and_creates_food_safety_incident(): void
    {
        $handoverService = app(SchoolHandoverService::class);

        $record = $handoverService->confirmReceipt(
            $this->stopA,
            $this->picSchoolA,
            [
                'received_portions' => 340,
                'damaged_portions' => 0,
                'appearance_ok' => true,
                'aroma_ok' => true,
                'texture_ok' => true,
                'conclusion' => 'SAFE',
            ]
        );

        // 2nd QC detects bad aroma and texture -> UNSAFE
        $this->actingAs($this->picSchoolA)
            ->post(route('school.meal-distribution.pre-consumption-check', $record), [
                'appearance_ok' => 1,
                'aroma_ok' => 0, // Basi
                'texture_ok' => 0,
                'taste_tested' => 0,
                'conclusion' => 'UNSAFE',
                'notes' => 'Makanan tercium bau asam basi menyengat.',
            ]);

        $record->refresh();
        $this->assertEquals(SchoolReceiptStatus::HELD, $record->status);

        // Incident verification
        $incident = Incident::where('subject_type', SchoolServiceRecord::class)
            ->where('subject_id', $record->id)
            ->first();

        $this->assertNotNull($incident);
        $this->assertEquals(IncidentCategory::FOOD_SAFETY, $incident->category);
        $this->assertEquals(IncidentSeverity::CRITICAL, $incident->severity);
        $this->assertEquals($this->picSchoolA->id, $incident->reported_by);
        $this->assertEquals($this->schoolA->id, $incident->school_id);
        $this->assertEquals($this->sppg->id, $incident->sppg_id);

        // Distribution is blocked when HELD
        $responseStart = $this->actingAs($this->picSchoolA)
            ->post(route('school.meal-distribution.start', $record));
        $responseStart->assertStatus(409);

        // Mark ready on HELD meal without exception fails
        $trayReturn = $record->trayReturn;
        $responseTray = $this->actingAs($this->picSchoolA)
            ->post(route('school.trays.ready', $trayReturn), [
                'ready_count' => 15,
            ]);
        $responseTray->assertStatus(409);

        // Mark ready on HELD meal is strictly blocked per Gate B, even if is_safety_exception is passed
        try {
            $trayService = app(TrayReturnService::class);
            $trayService->markReady($trayReturn, $this->picSchoolA, [
                'ready_count' => 15,
                'is_safety_exception' => true,
                'exception_reason' => 'Makanan rusak telah dibuang sesuai SOP pemusnahan pangan dan wadah telah disterilkan.',
            ]);
            $this->fail('Expected ConflictHttpException when marking ready on HELD meal service record.');
        } catch (\Symfony\Component\HttpKernel\Exception\ConflictHttpException $e) {
            $this->assertStringContainsString('Ditahan (Kendala Keamanan Pangan)', $e->getMessage());
        }
    }

    /**
     * Test that food suspected spoiled is NOT allowed to be tasted.
     */
    public function test_cannot_force_tasting_when_food_appearance_or_aroma_is_spoiled(): void
    {
        $handoverService = app(SchoolHandoverService::class);

        $record = $handoverService->confirmReceipt(
            $this->stopA,
            $this->picSchoolA,
            [
                'received_portions' => 340,
                'damaged_portions' => 0,
                'appearance_ok' => true,
                'aroma_ok' => true,
                'texture_ok' => true,
                'conclusion' => 'SAFE',
            ]
        );

        $response = $this->actingAs($this->picSchoolA)
            ->post(route('school.meal-distribution.pre-consumption-check', $record), [
                'appearance_ok' => 0, // Bad appearance
                'aroma_ok' => 1,
                'texture_ok' => 1,
                'taste_tested' => 1, // Tried to taste!
                'taste_ok' => 1,
                'conclusion' => 'UNSAFE',
            ]);

        $response->assertSessionHasErrors(['taste_tested']);
    }

    /**
     * Test portion bounds: served + leftover cannot exceed usable portions.
     */
    public function test_cannot_complete_meal_with_portions_exceeding_usable_bound(): void
    {
        $handoverService = app(SchoolHandoverService::class);

        // 340 received, 20 damaged -> usable = 320
        $record = $handoverService->confirmReceipt(
            $this->stopA,
            $this->picSchoolA,
            [
                'received_portions' => 340,
                'damaged_portions' => 20,
                'appearance_ok' => true,
                'aroma_ok' => true,
                'texture_ok' => true,
                'conclusion' => 'SAFE',
                'notes' => '20 porsi rusak dalam perjalanan.',
            ]
        );

        $handoverService->recordPreConsumptionCheck($record, $this->picSchoolA, [
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'conclusion' => 'SAFE',
        ]);

        $handoverService->startDistribution($record, $this->picSchoolA);

        // Attempting 320 served + 5 leftover = 325 > 320 usable
        $response = $this->actingAs($this->picSchoolA)
            ->post(route('school.meal-distribution.complete', $record), [
                'served_portions' => 320,
                'leftover_portions' => 5,
            ]);

        $response->assertSessionHasErrors(['served_portions']);

        // Attempting negative portions
        $responseNeg = $this->actingAs($this->picSchoolA)
            ->post(route('school.meal-distribution.complete', $record), [
                'served_portions' => -10,
                'leftover_portions' => 0,
            ]);

        $responseNeg->assertSessionHasErrors(['served_portions']);
    }

    /**
     * Test authorization: Cross-school PIC or courier cannot access or modify meal distribution.
     */
    public function test_cross_school_pic_and_unauthorized_roles_are_rejected(): void
    {
        $handoverService = app(SchoolHandoverService::class);

        $record = $handoverService->confirmReceipt(
            $this->stopA,
            $this->picSchoolA,
            [
                'received_portions' => 340,
                'damaged_portions' => 0,
                'appearance_ok' => true,
                'aroma_ok' => true,
                'texture_ok' => true,
                'conclusion' => 'SAFE',
            ]
        );

        // PIC of School B attempts to inspect School A's meal
        $responseQc = $this->actingAs($this->picSchoolB)
            ->post(route('school.meal-distribution.pre-consumption-check', $record), [
                'appearance_ok' => 1,
                'aroma_ok' => 1,
                'texture_ok' => 1,
                'conclusion' => 'SAFE',
            ]);
        $responseQc->assertStatus(403);

        // PIC of School B attempts to complete School A's meal
        $responseComplete = $this->actingAs($this->picSchoolB)
            ->post(route('school.meal-distribution.complete', $record), [
                'served_portions' => 340,
                'leftover_portions' => 0,
            ]);
        $responseComplete->assertStatus(403);

        // Courier attempts to access
        $responseCourier = $this->actingAs($this->driver)
            ->post(route('school.meal-distribution.start', $record));
        $responseCourier->assertStatus(403);
    }

    /**
     * Test that 2nd QC cannot be bypassed to complete meal.
     */
    public function test_cannot_skip_2nd_qc_to_reach_meal_completed(): void
    {
        $handoverService = app(SchoolHandoverService::class);

        $record = $handoverService->confirmReceipt(
            $this->stopA,
            $this->picSchoolA,
            [
                'received_portions' => 340,
                'damaged_portions' => 0,
                'appearance_ok' => true,
                'aroma_ok' => true,
                'texture_ok' => true,
                'conclusion' => 'SAFE',
            ]
        );

        // Directly call completeMeal without 2nd QC
        $response = $this->actingAs($this->picSchoolA)
            ->post(route('school.meal-distribution.complete', $record), [
                'served_portions' => 340,
                'leftover_portions' => 0,
            ]);

        $response->assertStatus(409);
    }

    /**
     * Test idempotency on repeated actions.
     */
    public function test_idempotency_on_double_submit(): void
    {
        $handoverService = app(SchoolHandoverService::class);

        $record = $handoverService->confirmReceipt(
            $this->stopA,
            $this->picSchoolA,
            [
                'received_portions' => 340,
                'damaged_portions' => 0,
                'appearance_ok' => true,
                'aroma_ok' => true,
                'texture_ok' => true,
                'conclusion' => 'SAFE',
            ]
        );

        // 1st 2nd QC
        $rec1 = $handoverService->recordPreConsumptionCheck($record, $this->picSchoolA, [
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'conclusion' => 'SAFE',
        ]);
        $this->assertEquals(SchoolReceiptStatus::CLEARED_TO_SERVE, $rec1->status);

        // 2nd 2nd QC (idempotent)
        $rec2 = $handoverService->recordPreConsumptionCheck($rec1, $this->picSchoolA, [
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'conclusion' => 'SAFE',
        ]);
        $this->assertEquals(SchoolReceiptStatus::CLEARED_TO_SERVE, $rec2->status);

        // Start distribution
        $dist1 = $handoverService->startDistribution($rec2, $this->picSchoolA);
        $startedAt = $dist1->distribution_started_at;

        // Repeat start distribution
        $dist2 = $handoverService->startDistribution($dist1, $this->picSchoolA);
        $this->assertEquals($startedAt->toIso8601String(), $dist2->distribution_started_at->toIso8601String());

        // Complete meal
        $comp1 = $handoverService->completeMeal($dist2, $this->picSchoolA, [
            'served_portions' => 340,
            'leftover_portions' => 0,
        ]);
        $this->assertEquals(SchoolReceiptStatus::MEAL_COMPLETED, $comp1->status);

        // Repeat complete meal
        $comp2 = $handoverService->completeMeal($comp1, $this->picSchoolA, [
            'served_portions' => 340,
            'leftover_portions' => 0,
        ]);
        $this->assertEquals(SchoolReceiptStatus::MEAL_COMPLETED, $comp2->status);
    }

    /**
     * Test Gate C: Privacy projections on trips.map-data, public map, and school delivery status.
     */
    public function test_gate_c_privacy_protections(): void
    {
        // 1. PIC School A mapData strict minimal projection
        $responsePic = $this->actingAs($this->picSchoolA)
            ->get(route('trips.map-data', $this->trip));

        $responsePic->assertOk();
        $picData = $responsePic->json();

        // Must nullify geometry and legs for PIC
        $this->assertNull($picData['geometry']);
        $this->assertEmpty($picData['legs']);
        $this->assertNull($picData['vehicle']);
        $this->assertNull($picData['driver']);
        $this->assertNull($picData['driver_telemetry']);
        $this->assertNull($picData['sppg']['lat']);
        $this->assertNull($picData['sppg']['lng']);
        $this->assertNull($picData['active_stop_sequence']);

        // PIC only receives coordinates for their own school; other schools have null lat/lng and masked names
        $myStopData = collect($picData['stops'])->firstWhere('is_my_school', true);
        $this->assertNotNull($myStopData);
        $this->assertEquals($this->stopA->id, $myStopData['id']);
        $this->assertEquals($this->schoolA->id, $myStopData['school_id']);
        $this->assertNotNull($myStopData['lat']);
        $this->assertNotNull($myStopData['lng']);

        $otherStopData = collect($picData['stops'])->firstWhere('is_my_school', false);
        $this->assertNotNull($otherStopData);
        $this->assertNull($otherStopData['school_id']);
        $this->assertNull($otherStopData['lat']);
        $this->assertNull($otherStopData['lng']);
        $this->assertStringContainsString('Pemberhentian', $otherStopData['school_name']);

        // 2. Public delivery map data - unfiltered request has empty stops
        $responsePublicUnfiltered = $this->get(route('public.trips.map-data', $this->trip->trip_code));
        $responsePublicUnfiltered->assertOk();
        $pubDataUnfiltered = $responsePublicUnfiltered->json();
        $this->assertEmpty($pubDataUnfiltered['stops']);
        $this->assertNull($pubDataUnfiltered['geometry']);
        $this->assertNull($pubDataUnfiltered['sppg']['lat']);
        $this->assertNull($pubDataUnfiltered['sppg']['lng']);

        // Public delivery map data - filtered by school
        $responsePublicFiltered = $this->get(route('public.trips.map-data', [
            'tripCode' => $this->trip->trip_code,
            'school' => $this->schoolA->public_slug,
        ]));
        $responsePublicFiltered->assertOk();
        $pubDataFiltered = $responsePublicFiltered->json();
        $this->assertCount(1, $pubDataFiltered['stops']);
        $this->assertArrayNotHasKey('sequence_number', $pubDataFiltered['stops'][0]);
        $this->assertNull($pubDataFiltered['stops'][0]['lat']);
        $this->assertNull($pubDataFiltered['stops'][0]['lng']);
        $this->assertNull($pubDataFiltered['geometry']);

        // 3. Public school delivery status (Page U04 minimal projection)
        $slug = $this->schoolA->public_slug ?: $this->schoolA->school_code;
        $responseU04 = $this->get(route('public.schools.delivery-status', ['slug' => $slug]));
        $responseU04->assertOk();

        $responseU04Json = $this->getJson(route('public.schools.delivery-status', ['slug' => $slug]));
        $responseU04Json->assertOk();
        $u04Data = $responseU04Json->json();

        $this->assertArrayHasKey('school', $u04Data);
        $this->assertArrayHasKey('delivery_stage', $u04Data);
        $this->assertArrayHasKey('is_arrived', $u04Data);
        $this->assertArrayHasKey('is_received', $u04Data);
        $this->assertArrayHasKey('eta', $u04Data);
        $this->assertArrayHasKey('is_live', $u04Data);
        $this->assertArrayNotHasKey('driver', $u04Data);
        $this->assertArrayNotHasKey('geometry', $u04Data);
        $this->assertArrayNotHasKey('telemetry', $u04Data);
    }

    /**
     * Test Gate C Negative: Cross-school PIC cannot access map data of trips not serving their school.
     */
    public function test_gate_c_pic_cross_school_access_is_forbidden(): void
    {
        $otherSchool = School::create([
            'sppg_id' => $this->sppg->id,
            'name' => 'SD Negeri Unrelated',
            'school_code' => 'SCH-UNRELATED',
            'npsn' => '99887766',
            'education_level' => 'SD',
            'address' => 'Jl. Unrelated No. 1',
            'latitude' => -7.300000,
            'longitude' => 112.750000,
            'public_slug' => 'sdn-unrelated',
        ]);
        $otherPic = User::factory()->create([
            'role' => UserRole::PIC_SEKOLAH,
            'school_id' => $otherSchool->id,
            'sppg_id' => $this->sppg->id,
        ]);

        $response = $this->actingAs($otherPic)
            ->get(route('trips.map-data', $this->trip));

        $response->assertStatus(403);
    }

    /**
     * Test Gate A Multi-Batch QC: Verified physical breakdown properly attributes portions per batch.
     */
    public function test_gate_a_multi_batch_qc_verified_breakdown_attribution(): void
    {
        // Create second batch
        $batch2 = FoodBatch::create([
            'production_plan_id' => $this->plan->id,
            'menu_id' => $this->menu->id,
            'batch_code' => 'BATCH-TEST-002',
            'planned_portions' => 200,
            'status' => FoodBatchStatus::RELEASED,
            'prepared_portions' => 200,
            'created_by' => $this->batch->created_by,
        ]);

        // Add second delivery item to stopA
        $item1 = $this->stopA->deliveryItems()->first();
        $item1->update(['planned_portions' => 200, 'loaded_portions' => 200]);
        $item2 = DeliveryItem::create([
            'trip_stop_id' => $this->stopA->id,
            'food_batch_id' => $batch2->id,
            'production_allocation_id' => $item1->production_allocation_id,
            'planned_portions' => 140,
            'loaded_portions' => 140,
        ]);

        $handoverService = app(SchoolHandoverService::class);
        $recordA = $handoverService->confirmReceipt(
            $this->stopA,
            $this->picSchoolA,
            [
                'received_portions' => 340,
                'damaged_portions' => 0,
                'notes' => 'Penerimaan multi-batch porsi lengkap.',
                'appearance_ok' => true,
                'aroma_ok' => true,
                'texture_ok' => true,
                'taste_tested' => true,
                'taste_ok' => true,
                'temperature_c' => 65.0,
                'conclusion' => 'SAFE',
            ]
        );

        // Submit verified physical breakdown
        $qcData = [
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => QualityCheckConclusion::SAFE->value,
            'notes' => 'Pemeriksaan multi-batch lengkap dan terverifikasi.',
            'items' => [
                $item1->id => [
                    'received_portions' => 200,
                    'damaged_portions' => 0,
                ],
                $item2->id => [
                    'received_portions' => 140,
                    'damaged_portions' => 0,
                ],
            ],
        ];

        $handoverService->recordPreConsumptionCheck($recordA, $this->picSchoolA, $qcData);

        $recordA->refresh();
        $this->assertEquals(SchoolReceiptStatus::CLEARED_TO_SERVE, $recordA->status);

        // Verify quality checks created for both batches with accurate portions
        $qcBatch1 = QualityCheck::where('food_batch_id', $this->batch->id)
            ->where('check_stage', QualityCheckStage::BEFORE_CONSUMPTION)
            ->latest('id')
            ->first();
        $this->assertNotNull($qcBatch1);
        $this->assertEquals(200, $qcBatch1->accepted_portions);
        $this->assertEquals(0, $qcBatch1->rejected_portions);

        $qcBatch2 = QualityCheck::where('food_batch_id', $batch2->id)
            ->where('check_stage', QualityCheckStage::BEFORE_CONSUMPTION)
            ->latest('id')
            ->first();
        $this->assertNotNull($qcBatch2);
        $this->assertEquals(140, $qcBatch2->accepted_portions);
        $this->assertEquals(0, $qcBatch2->rejected_portions);
    }

    /**
     * Test Gate A Multi-Batch QC: Inconsistent breakdown safely falls back to unknown/null attribution.
     */
    public function test_gate_a_multi_batch_qc_unverified_breakdown_falls_back_to_null(): void
    {
        $batch2 = FoodBatch::create([
            'production_plan_id' => $this->plan->id,
            'menu_id' => $this->menu->id,
            'batch_code' => 'BATCH-TEST-003',
            'planned_portions' => 200,
            'status' => FoodBatchStatus::RELEASED,
            'prepared_portions' => 200,
            'created_by' => $this->batch->created_by,
        ]);

        $item1 = $this->stopA->deliveryItems()->first();
        $item1->update(['planned_portions' => 200, 'loaded_portions' => 200]);
        $item2 = DeliveryItem::create([
            'trip_stop_id' => $this->stopA->id,
            'food_batch_id' => $batch2->id,
            'production_allocation_id' => $item1->production_allocation_id,
            'planned_portions' => 140,
            'loaded_portions' => 140,
        ]);

        $handoverService = app(SchoolHandoverService::class);
        $recordA = $handoverService->confirmReceipt(
            $this->stopA,
            $this->picSchoolA,
            [
                'received_portions' => 340,
                'damaged_portions' => 0,
                'notes' => 'Penerimaan multi-batch porsi lengkap.',
                'appearance_ok' => true,
                'aroma_ok' => true,
                'texture_ok' => true,
                'taste_tested' => true,
                'taste_ok' => true,
                'temperature_c' => 65.0,
                'conclusion' => 'SAFE',
            ]
        );

        // Submit INCONSISTENT breakdown (sum = 300, but record has 340)
        $qcData = [
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'conclusion' => QualityCheckConclusion::SAFE->value,
            'notes' => 'Pemeriksaan multi-batch agregat.',
            'items' => [
                $item1->id => [
                    'received_portions' => 200,
                    'damaged_portions' => 0,
                ],
                $item2->id => [
                    'received_portions' => 100, // Inconsistent: 200 + 100 = 300 != 340
                    'damaged_portions' => 0,
                ],
            ],
        ];

        $handoverService->recordPreConsumptionCheck($recordA, $this->picSchoolA, $qcData);

        $recordA->refresh();
        $this->assertEquals(SchoolReceiptStatus::CLEARED_TO_SERVE, $recordA->status);

        // Verify quality checks created with NULL attribution per food traceability rules
        $qcBatch1 = QualityCheck::where('food_batch_id', $this->batch->id)
            ->where('check_stage', QualityCheckStage::BEFORE_CONSUMPTION)
            ->latest('id')
            ->first();
        $this->assertNotNull($qcBatch1);
        $this->assertNull($qcBatch1->accepted_portions);
        $this->assertNull($qcBatch1->rejected_portions);
        $this->assertStringContainsString('unknown (null)', $qcBatch1->decision_reason);
    }

    /**
     * Test Gate D: Incident ownership validation resolves SPPG ID for FoodBatch and QualityCheck.
     */
    public function test_gate_d_incident_food_batch_and_qc_ownership_resolution(): void
    {
        $incidentService = app(\App\Services\IncidentService::class);
        $kepala = User::where('role', UserRole::KEPALA_SPPG)->firstOrFail();

        // 1. FoodBatch subject
        $batchIncident = $incidentService->createIncident([
            'category' => IncidentCategory::FOOD_SAFETY->value,
            'severity' => IncidentSeverity::HIGH->value,
            'subject_type' => FoodBatch::class,
            'subject_id' => $this->batch->id,
            'description' => 'Suhu ruang pendingin batch tidak sesuai batas aman sebelum porsi.',
        ], $kepala);

        $this->assertNotNull($batchIncident);
        $this->assertEquals($this->sppg->id, $batchIncident->sppg_id);
        $this->assertEquals(FoodBatch::class, $batchIncident->subject_type);
        $this->assertEquals($this->batch->id, $batchIncident->subject_id);

        // 2. QualityCheck subject
        $qc = QualityCheck::create([
            'food_batch_id' => $this->batch->id,
            'check_stage' => QualityCheckStage::BEFORE_DISPATCH,
            'checked_by' => $kepala->id,
            'conclusion' => QualityCheckConclusion::UNSAFE,
            'checked_at' => Carbon::now(),
        ]);

        $qcIncident = $incidentService->createIncident([
            'category' => IncidentCategory::FOOD_SAFETY->value,
            'severity' => IncidentSeverity::CRITICAL->value,
            'subject_type' => QualityCheck::class,
            'subject_id' => $qc->id,
            'description' => 'Hasil uji organoleptik sebelum distribusi tidak lolos.',
        ], $kepala);

        $this->assertNotNull($qcIncident);
        $this->assertEquals($this->sppg->id, $qcIncident->sppg_id);
        $this->assertEquals(QualityCheck::class, $qcIncident->subject_type);
        $this->assertEquals($qc->id, $qcIncident->subject_id);
    }
}

