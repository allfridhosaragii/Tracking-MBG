<?php

namespace Tests\Feature;

use App\Enums\FoodBatchStatus;
use App\Enums\PortionRequestStatus;
use App\Enums\ProductionPlanStatus;
use App\Enums\QualityCheckConclusion;
use App\Enums\QualityCheckStage;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\DailyPortionRequest;
use App\Models\DeliveryItem;
use App\Models\FoodBatch;
use App\Models\Menu;
use App\Models\ProductionAllocation;
use App\Models\ProductionPlan;
use App\Models\QualityCheck;
use App\Models\School;
use App\Models\Sppg;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\TripDispatchService;
use Carbon\Carbon;
use Database\Seeders\MbgTrackBaselineSeeder;
use Database\Seeders\MenuBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Pbi08IntegrityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected Sppg $sppgA;
    protected School $schoolA1;
    protected School $schoolA2;
    protected Menu $menuA;
    protected User $asistenA;
    protected User $giziA;
    protected User $kepalaA;
    protected User $driverA;
    protected User $otherDriver;
    protected Vehicle $vehicleA;
    protected ProductionPlan $planToday;
    protected FoodBatch $batchToday;
    protected DeliveryItem $item1;
    protected DeliveryItem $item2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MbgTrackBaselineSeeder::class);
        $this->seed(MenuBaselineSeeder::class);

        $this->sppgA = Sppg::where('code', 'SPPG-SUB-01')->firstOrFail();
        $this->schoolA1 = School::where('school_code', 'SCH-SDN-01')->firstOrFail();
        $this->schoolA2 = School::where('school_code', 'SCH-SMPN-02')->firstOrFail();

        $this->asistenA = User::where('email', 'asisten@mbgtrack.test')->firstOrFail();
        $this->giziA = User::where('email', 'gizi@mbgtrack.test')->firstOrFail();
        $this->kepalaA = User::where('email', 'kepala@mbgtrack.test')->firstOrFail();
        $this->driverA = User::where('email', 'kurir@mbgtrack.test')->firstOrFail();

        $this->otherDriver = User::create([
            'sppg_id' => $this->sppgA->id,
            'name' => 'Kurir Lain SPPG A',
            'email' => 'kurir_lain@mbgtrack.test',
            'phone' => '081234567899',
            'password' => bcrypt('password'),
            'role' => UserRole::KURIR,
            'is_active' => true,
        ]);

        $this->vehicleA = Vehicle::create([
            'sppg_id' => $this->sppgA->id,
            'plate_number' => 'L 9999 PBI',
            'vehicle_name' => 'Armada Audit PBI-08',
            'vehicle_type' => 'VAN',
            'capacity_portions' => 800,
            'is_active' => true,
        ]);

        $this->menuA = Menu::where('sppg_id', $this->sppgA->id)->firstOrFail();
        $today = Carbon::today()->toDateString();

        $this->planToday = ProductionPlan::create([
            'sppg_id' => $this->sppgA->id,
            'menu_id' => $this->menuA->id,
            'plan_code' => 'PLAN-AUDIT-01',
            'service_date' => $today,
            'total_target_portions' => 350,
            'status' => ProductionPlanStatus::FINALIZED,
            'created_by' => $this->kepalaA->id,
            'finalized_by' => $this->kepalaA->id,
            'finalized_at' => Carbon::now(),
        ]);

        $req1 = DailyPortionRequest::create([
            'school_id' => $this->schoolA1->id,
            'sppg_id' => $this->sppgA->id,
            'service_date' => $today,
            'registered_count' => 200,
            'requested_portions' => 200,
            'status' => PortionRequestStatus::CONFIRMED,
            'created_by' => $this->asistenA->id,
        ]);
        $alloc1 = ProductionAllocation::create([
            'production_plan_id' => $this->planToday->id,
            'portion_request_id' => $req1->id,
            'school_id' => $this->schoolA1->id,
            'allocated_portions' => 200,
        ]);

        $req2 = DailyPortionRequest::create([
            'school_id' => $this->schoolA2->id,
            'sppg_id' => $this->sppgA->id,
            'service_date' => $today,
            'registered_count' => 150,
            'requested_portions' => 150,
            'status' => PortionRequestStatus::CONFIRMED,
            'created_by' => $this->asistenA->id,
        ]);
        $alloc2 = ProductionAllocation::create([
            'production_plan_id' => $this->planToday->id,
            'portion_request_id' => $req2->id,
            'school_id' => $this->schoolA2->id,
            'allocated_portions' => 150,
        ]);

        $this->batchToday = FoodBatch::create([
            'production_plan_id' => $this->planToday->id,
            'menu_id' => $this->menuA->id,
            'batch_code' => 'BATCH-AUDIT-01',
            'planned_portions' => 350,
            'actual_portions' => 350,
            'accepted_portions' => 350,
            'status' => FoodBatchStatus::RELEASED,
            'created_by' => $this->asistenA->id,
        ]);

        QualityCheck::create([
            'food_batch_id' => $this->batchToday->id,
            'stage' => QualityCheckStage::BEFORE_DISPATCH,
            'checked_by' => $this->giziA->id,
            'temperature_celsius' => 67.0,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'accepted_portions' => 350,
            'conclusion' => QualityCheckConclusion::SAFE,
            'checked_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->item1 = DeliveryItem::create([
            'food_batch_id' => $this->batchToday->id,
            'production_allocation_id' => $alloc1->id,
            'planned_portions' => 200,
            'released_portions' => 200,
            'loaded_portions' => null,
            'status' => 'RELEASED',
        ]);

        $this->item2 = DeliveryItem::create([
            'food_batch_id' => $this->batchToday->id,
            'production_allocation_id' => $alloc2->id,
            'planned_portions' => 150,
            'released_portions' => 150,
            'loaded_portions' => null,
            'status' => 'RELEASED',
        ]);
    }

    /**
     * Helper to create a 2-stop trip.
     */
    protected function createTwoStopTrip(): Trip
    {
        $service = app(TripDispatchService::class);

        return $service->createTrip([
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        ['delivery_item_id' => $this->item1->id, 'planned_portions' => 200],
                    ],
                ],
                [
                    'school_id' => $this->schoolA2->id,
                    'sequence_number' => 2,
                    'items' => [
                        ['delivery_item_id' => $this->item2->id, 'planned_portions' => 150],
                    ],
                ],
            ],
        ], $this->kepalaA);
    }

    /**
     * CRITICAL GATE A: Reproduce and verify AJAX swipe-to-depart JSON response.
     */
    public function test_ajax_swipe_to_depart_returns_consistent_json(): void
    {
        $trip = $this->createTwoStopTrip();
        $service = app(TripDispatchService::class);

        // Complete gates properly
        $service->verifyLoading($trip, [
            'items' => [
                $this->item1->id => 200,
                $this->item2->id => 150,
            ],
        ], $this->asistenA);
        $service->checkVehicle($trip, ['passed' => true], $this->driverA);
        $service->approveDispatch($trip, $this->kepalaA);

        $trip->refresh();
        $this->assertEquals(TripStatus::READY, $trip->status);

        // 1. AJAX request from swipe-slider.js with Accept: application/json
        $response = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.start', $trip), ['swiped' => true]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'trip_status' => 'IN_PROGRESS',
            'tracking_status' => 'WAITING_FOR_LOCATION',
        ]);

        $trip->refresh();
        $this->assertEquals(TripStatus::IN_PROGRESS, $trip->status);

        // 2. Status reconciliation: If kurir retries/swipes again when already IN_PROGRESS
        $retryResponse = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.start', $trip), ['swiped' => true]);

        // Must reconcile gracefully without error 500 or duplicate activity log
        $this->assertTrue(in_array($retryResponse->status(), [200, 409], true));

        $startLogs = ActivityLog::where('auditable_type', Trip::class)
            ->where('auditable_id', $trip->id)
            ->where('event', 'trip.started')
            ->count();
        $this->assertEquals(1, $startLogs, 'Duplicate trip.started activity logs must be prevented upon retry.');
    }

    /**
     * CRITICAL GATE B: Partial manifest loading verification MUST be rejected.
     */
    public function test_partial_manifest_loading_verification_must_be_rejected(): void
    {
        $trip = $this->createTwoStopTrip();
        $service = app(TripDispatchService::class);

        // Trip has 2 items: item1 (200) and item2 (150).
        // Sending payload with ONLY item1 must throw ValidationException!
        $this->expectException(ValidationException::class);
        $service->verifyLoading($trip, [
            'items' => [
                $this->item1->id => 200,
            ],
        ], $this->asistenA);
    }

    /**
     * CRITICAL GATE B: Duplicate item loading must be rejected.
     */
    public function test_duplicate_item_loading_is_rejected(): void
    {
        $trip = $this->createTwoStopTrip();
        $service = app(TripDispatchService::class);

        $this->expectException(ValidationException::class);
        $service->verifyLoading($trip, [
            'items' => [
                ['delivery_item_id' => $this->item1->id, 'quantity' => 200],
                ['delivery_item_id' => $this->item1->id, 'quantity' => 200], // duplicate
            ],
        ], $this->asistenA);
    }

    /**
     * CRITICAL GATE C: Modifying manifest after approval must automatically revoke approval.
     */
    public function test_manifest_change_after_approval_revokes_approval(): void
    {
        $trip = $this->createTwoStopTrip();
        $service = app(TripDispatchService::class);

        $service->verifyLoading($trip, [
            'items' => [
                $this->item1->id => 200,
                $this->item2->id => 150,
            ],
        ], $this->asistenA);
        $service->checkVehicle($trip, ['passed' => true], $this->driverA);
        $service->approveDispatch($trip, $this->kepalaA);

        $trip->refresh();
        $this->assertEquals(TripStatus::READY, $trip->status);
        $this->assertTrue($trip->dispatch_approved);

        // Re-verify/modify loading
        $service->verifyLoading($trip, [
            'items' => [
                $this->item1->id => 190,
                $this->item2->id => 150,
            ],
        ], $this->asistenA);

        $trip->refresh();
        $this->assertFalse($trip->dispatch_approved, 'Prior approval must be revoked when manifest changes.');
        $this->assertNotEquals(TripStatus::READY, $trip->status, 'Trip status must revert from READY when manifest changes.');
    }

    /**
     * CRITICAL GATE C: Held food batch blocks departure even if previously approved.
     */
    public function test_held_batch_blocks_departure(): void
    {
        $trip = $this->createTwoStopTrip();
        $service = app(TripDispatchService::class);

        $service->verifyLoading($trip, [
            'items' => [
                $this->item1->id => 200,
                $this->item2->id => 150,
            ],
        ], $this->asistenA);
        $service->checkVehicle($trip, ['passed' => true], $this->driverA);
        $service->approveDispatch($trip, $this->kepalaA);

        $trip->refresh();
        $this->assertEquals(TripStatus::READY, $trip->status);

        // Hold the batch due to post-dispatch food safety incident
        $this->batchToday->update(['status' => FoodBatchStatus::ON_HOLD]);

        $this->expectException(ValidationException::class);
        $service->startTrip($trip, $this->driverA);
    }

    /**
     * CRITICAL GATE C: Inactive vehicle blocks departure.
     */
    public function test_inactive_vehicle_blocks_departure(): void
    {
        $trip = $this->createTwoStopTrip();
        $service = app(TripDispatchService::class);

        $service->verifyLoading($trip, [
            'items' => [
                $this->item1->id => 200,
                $this->item2->id => 150,
            ],
        ], $this->asistenA);
        $service->checkVehicle($trip, ['passed' => true], $this->driverA);
        $service->approveDispatch($trip, $this->kepalaA);

        $trip->refresh();
        $this->vehicleA->update(['is_active' => false]);

        $this->expectException(ValidationException::class);
        $service->startTrip($trip, $this->driverA);
    }

    /**
     * HIGH GATE D: PIC Sekolah only receives sanitized data for their own school.
     */
    public function test_pic_sekolah_map_data_isolates_and_masks_other_schools(): void
    {
        $trip = $this->createTwoStopTrip();
        $service = app(TripDispatchService::class);

        $service->verifyLoading($trip, [
            'items' => [
                $this->item1->id => 200,
                $this->item2->id => 150,
            ],
        ], $this->asistenA);

        // Create PIC for School 1
        $picSchool1 = User::create([
            'sppg_id' => $this->sppgA->id,
            'school_id' => $this->schoolA1->id,
            'name' => 'PIC SDN 01',
            'email' => 'pic_sdn01@mbgtrack.test',
            'phone' => '081234567888',
            'password' => bcrypt('password'),
            'role' => UserRole::PIC_SEKOLAH,
            'is_active' => true,
        ]);

        $response = $this->actingAs($picSchool1)
            ->getJson(route('trips.map-data', $trip));

        $response->assertStatus(200);
        $data = $response->json();

        // Must NOT expose driver or vehicle objects
        $this->assertNull($data['driver']);
        $this->assertNull($data['vehicle']);

        // Stop 1 is PIC's own school
        $stop1 = collect($data['stops'])->firstWhere('sequence_number', 1);
        $this->assertEquals($this->schoolA1->name, $stop1['school_name']);
        $this->assertEquals(200, $stop1['loaded_portions']);
        $this->assertTrue($stop1['is_my_school']);

        // Stop 2 is another school -> must be masked
        $stop2 = collect($data['stops'])->firstWhere('sequence_number', 2);
        $this->assertEquals('Pemberhentian #2', $stop2['school_name']);
        $this->assertNull($stop2['school_address']);
        $this->assertNull($stop2['planned_portions']);
        $this->assertNull($stop2['loaded_portions']);
        $this->assertFalse($stop2['is_my_school']);
    }

    /**
     * HIGH GATE F: Public trip cannot be accessed before departure (ASSIGNED/READY -> 404).
     */
    public function test_public_trip_before_departure_cannot_be_accessed(): void
    {
        $trip = $this->createTwoStopTrip();

        // 1. Show page must return 404
        $pageResponse = $this->get(route('public.trips.map', $trip->trip_code));
        $pageResponse->assertStatus(404);

        // 2. Map-data API must return 404
        $apiResponse = $this->getJson(route('public.trips.map-data', $trip->trip_code));
        $apiResponse->assertStatus(404);
    }

    /**
     * HIGH GATE F: Public trip in progress returns strictly whitelisted data.
     */
    public function test_public_trip_in_progress_returns_whitelisted_data(): void
    {
        $trip = $this->createTwoStopTrip();
        $service = app(TripDispatchService::class);

        $service->verifyLoading($trip, [
            'items' => [
                $this->item1->id => 200,
                $this->item2->id => 150,
            ],
        ], $this->asistenA);
        $service->checkVehicle($trip, ['passed' => true], $this->driverA);
        $service->approveDispatch($trip, $this->kepalaA);
        $service->startTrip($trip, $this->driverA);

        $trip->refresh();
        // Unfiltered query has empty stops per privacy protection
        $responseUnfiltered = $this->getJson(route('public.trips.map-data', $trip->trip_code));
        $responseUnfiltered->assertStatus(200);
        $this->assertEmpty($responseUnfiltered->json('stops'));

        // Filtered query by school provides sanitized stop projection
        $response = $this->getJson(route('public.trips.map-data', [
            'tripCode' => $trip->trip_code,
            'school' => $this->schoolA1->public_slug,
        ]));
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertEquals($trip->trip_code, $data['trip_code']);
        $this->assertEquals('IN_PROGRESS', $data['status']);

        // Public whitelist: No driver name, no vehicle plate, no internal notes
        $this->assertArrayNotHasKey('driver', $data);
        $this->assertArrayNotHasKey('vehicle', $data);
        $this->assertArrayNotHasKey('loading_notes', $data);

        // Each stop has sanitized stage label
        foreach ($data['stops'] as $stop) {
            $this->assertNotEmpty($stop['stage']);
            $this->assertArrayNotHasKey('driver', $stop);
            $this->assertArrayNotHasKey('driver_phone', $stop);
        }
    }

    /**
     * HIGH GATE G: Public map data handles all TripStopStatus enums without UnhandledMatchError.
     */
    public function test_public_map_data_handles_all_trip_stop_statuses_without_error(): void
    {
        $trip = $this->createTwoStopTrip();
        $trip->update(['status' => TripStatus::IN_PROGRESS]);

        $stops = $trip->stops;
        $allStatuses = \App\Enums\TripStopStatus::cases();

        foreach ($allStatuses as $statusCase) {
            $stops->first()->update(['status' => $statusCase]);

            $response = $this->getJson(route('public.trips.map-data', [
                'tripCode' => $trip->trip_code,
                'school' => $stops->first()->school->public_slug,
            ]));
            $response->assertStatus(200);
            $stopData = collect($response->json('stops'))->first();
            $this->assertNotNull($stopData);
            $this->assertNotEmpty($stopData['stage']);
        }
    }

    /**
     * ROUTING QUALITY GATE: OSRM failure has short cache TTL.
     */
    public function test_osrm_failure_has_short_cache_ttl(): void
    {
        $routingService = app(\App\Services\OsrmRoutingService::class);
        $waypoints = [
            ['lat' => -7.288, 'lng' => 112.735, 'name' => 'Point 1'],
            ['lat' => -7.290, 'lng' => 112.740, 'name' => 'Point 2'],
        ];

        // Fake HTTP failure
        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response('Service Unavailable', 503),
        ]);

        $routingService->invalidateRouteCache($waypoints);
        $result = $routingService->getRoute($waypoints);

        $this->assertFalse($result['success']);
        $this->assertNull($result['geometry']);

        $cacheKey = $routingService->getCacheKey($waypoints);
        $this->assertTrue(\Illuminate\Support\Facades\Cache::has($cacheKey));
    }
}
