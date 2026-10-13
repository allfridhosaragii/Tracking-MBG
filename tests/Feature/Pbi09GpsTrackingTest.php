<?php

namespace Tests\Feature;

use App\Enums\FoodBatchStatus;
use App\Enums\PortionRequestStatus;
use App\Enums\ProductionPlanStatus;
use App\Enums\QualityCheckConclusion;
use App\Enums\QualityCheckStage;
use App\Enums\TripStatus;
use App\Enums\TripStopStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\DailyPortionRequest;
use App\Models\DeliveryItem;
use App\Models\FoodBatch;
use App\Models\GpsLocation;
use App\Models\Menu;
use App\Models\ProductionAllocation;
use App\Models\ProductionPlan;
use App\Models\QualityCheck;
use App\Models\School;
use App\Models\Sppg;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\GpsTrackingService;
use App\Services\TripDispatchService;
use Carbon\Carbon;
use Database\Seeders\MbgTrackBaselineSeeder;
use Database\Seeders\MenuBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Pbi09GpsTrackingTest extends TestCase
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
    protected Trip $inProgressTrip;

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
            'plate_number' => 'L 8888 GPS',
            'vehicle_name' => 'Armada GPS PBI-09',
            'vehicle_type' => 'VAN',
            'capacity_portions' => 800,
            'is_active' => true,
        ]);

        $this->menuA = Menu::where('sppg_id', $this->sppgA->id)->firstOrFail();
        $today = Carbon::today()->toDateString();

        $planToday = ProductionPlan::create([
            'sppg_id' => $this->sppgA->id,
            'menu_id' => $this->menuA->id,
            'plan_code' => 'PLAN-GPS-01',
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
            'production_plan_id' => $planToday->id,
            'portion_request_id' => $req1->id,
            'school_id' => $this->schoolA1->id,
            'allocated_portions' => 200,
        ]);

        $batchToday = FoodBatch::create([
            'production_plan_id' => $planToday->id,
            'menu_id' => $this->menuA->id,
            'batch_code' => 'BATCH-GPS-01',
            'planned_portions' => 350,
            'actual_portions' => 350,
            'accepted_portions' => 350,
            'status' => FoodBatchStatus::RELEASED,
            'created_by' => $this->asistenA->id,
        ]);

        QualityCheck::create([
            'food_batch_id' => $batchToday->id,
            'stage' => QualityCheckStage::BEFORE_DISPATCH,
            'checked_by' => $this->giziA->id,
            'temperature_celsius' => 66.5,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'accepted_portions' => 350,
            'conclusion' => QualityCheckConclusion::SAFE,
            'checked_at' => Carbon::now()->subMinutes(30),
        ]);

        $item1 = DeliveryItem::create([
            'food_batch_id' => $batchToday->id,
            'production_allocation_id' => $alloc1->id,
            'planned_portions' => 200,
            'released_portions' => 200,
            'loaded_portions' => null,
            'status' => 'RELEASED',
        ]);

        $dispatchService = app(TripDispatchService::class);
        $trip = $dispatchService->createTrip([
            'service_date' => $today,
            'vehicle_id' => $this->vehicleA->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        ['delivery_item_id' => $item1->id, 'planned_portions' => 200],
                    ],
                ],
            ],
        ], $this->kepalaA);

        $dispatchService->verifyLoading($trip, [
            'items' => [$item1->id => 200],
        ], $this->asistenA);
        $dispatchService->checkVehicle($trip, ['passed' => true], $this->driverA);
        $dispatchService->approveDispatch($trip, $this->kepalaA);
        $this->inProgressTrip = $dispatchService->startTrip($trip, $this->driverA);
    }

    /**
     * PBI-09: Authenticated driver can record valid real GPS telemetry point.
     */
    public function test_driver_can_record_valid_gps_telemetry(): void
    {
        $response = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.telemetry', $this->inProgressTrip), [
                'latitude' => -7.288543,
                'longitude' => 112.735123,
                'accuracy' => 12.5,
                'speed' => 8.2,
                'heading' => 145.0,
                'recorded_at' => Carbon::now()->toIso8601String(),
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'tracking_status' => 'ONLINE',
            'latitude' => -7.288543,
            'longitude' => 112.735123,
            'accuracy_m' => 12.5,
        ]);

        $this->assertDatabaseHas('gps_locations', [
            'trip_id' => $this->inProgressTrip->id,
            'driver_id' => $this->driverA->id,
        ]);

        $this->inProgressTrip->refresh();
        $this->assertEquals(-7.288543, (float) $this->inProgressTrip->last_latitude);
        $this->assertEquals(112.735123, (float) $this->inProgressTrip->last_longitude);
        $this->assertEquals(12.5, (float) $this->inProgressTrip->last_accuracy_m);
        $this->assertNotNull($this->inProgressTrip->last_location_at);
    }

    /**
     * PBI-09: Unassigned driver cannot submit telemetry for another driver's trip.
     */
    public function test_unassigned_driver_cannot_record_telemetry(): void
    {
        $response = $this->actingAs($this->otherDriver)
            ->postJson(route('driver.trips.telemetry', $this->inProgressTrip), [
                'latitude' => -7.288543,
                'longitude' => 112.735123,
                'accuracy' => 15.0,
                'recorded_at' => Carbon::now()->toIso8601String(),
            ]);

        $response->assertStatus(403);
    }

    /**
     * PBI-09: Telemetry rejected when trip is not active (IN_PROGRESS).
     */
    public function test_telemetry_rejected_when_trip_is_not_in_progress(): void
    {
        $this->inProgressTrip->update(['status' => TripStatus::COMPLETED]);

        $response = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.telemetry', $this->inProgressTrip), [
                'latitude' => -7.288543,
                'longitude' => 112.735123,
                'accuracy' => 10.0,
                'recorded_at' => Carbon::now()->toIso8601String(),
            ]);

        $response->assertStatus(422);
    }

    /**
     * PBI-09: Telemetry with invalid coordinates rejected.
     */
    public function test_telemetry_with_invalid_coordinates_rejected(): void
    {
        // 1. Placeholder (0,0)
        $response1 = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.telemetry', $this->inProgressTrip), [
                'latitude' => 0.0,
                'longitude' => 0.0,
                'accuracy' => 10.0,
                'recorded_at' => Carbon::now()->toIso8601String(),
            ]);
        $response1->assertStatus(422);

        // 2. Out of bounds latitude (> 90)
        $response2 = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.telemetry', $this->inProgressTrip), [
                'latitude' => 95.123,
                'longitude' => 112.735,
                'accuracy' => 10.0,
                'recorded_at' => Carbon::now()->toIso8601String(),
            ]);
        $response2->assertStatus(422);
    }

    /**
     * PBI-09: Extreme future timestamp skew rejected.
     */
    public function test_telemetry_with_extreme_future_time_skew_rejected(): void
    {
        $response = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.telemetry', $this->inProgressTrip), [
                'latitude' => -7.288543,
                'longitude' => 112.735123,
                'accuracy' => 10.0,
                'recorded_at' => Carbon::now()->addHours(2)->toIso8601String(), // 2 hours future
            ]);

        $response->assertStatus(422);
    }

    /**
     * PBI-09: Tracking status accurately distinguishes ONLINE, STALE, and OFFLINE.
     */
    public function test_driver_tracking_status_detects_online_stale_and_offline(): void
    {
        $trackingService = app(GpsTrackingService::class);

        // 1. Fresh location (< 120s) -> ONLINE
        $this->inProgressTrip->update([
            'last_latitude' => -7.288543,
            'last_longitude' => 112.735123,
            'last_accuracy_m' => 10.0,
            'last_location_at' => Carbon::now()->subSeconds(30),
        ]);
        $this->inProgressTrip->refresh();
        $status1 = $trackingService->getDriverTrackingStatus($this->inProgressTrip);
        $this->assertEquals('ONLINE', $status1['status']);
        $this->assertTrue($status1['is_online']);

        // 2. Stale location (3 minutes ago) -> STALE
        $this->inProgressTrip->update([
            'last_location_at' => Carbon::now()->subMinutes(3),
        ]);
        $this->inProgressTrip->refresh();
        $status2 = $trackingService->getDriverTrackingStatus($this->inProgressTrip);
        $this->assertEquals('STALE', $status2['status']);
        $this->assertTrue($status2['is_stale']);

        // 3. Offline location (15 minutes ago) -> OFFLINE
        $this->inProgressTrip->update([
            'last_location_at' => Carbon::now()->subMinutes(15),
        ]);
        $this->inProgressTrip->refresh();
        $status3 = $trackingService->getDriverTrackingStatus($this->inProgressTrip);
        $this->assertEquals('OFFLINE', $status3['status']);
        $this->assertTrue($status3['is_offline']);
    }

    /**
     * PBI-09: PIC Sekolah does NOT receive raw driver telemetry coordinates.
     */
    public function test_pic_sekolah_does_not_receive_raw_driver_telemetry(): void
    {
        $this->inProgressTrip->update([
            'last_latitude' => -7.288543,
            'last_longitude' => 112.735123,
            'last_accuracy_m' => 10.0,
            'last_location_at' => Carbon::now(),
        ]);

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
            ->getJson(route('trips.map-data', $this->inProgressTrip));

        $response->assertStatus(200);
        $this->assertNull($response->json('driver_telemetry'), 'PIC Sekolah must never receive raw driver telemetry coordinates.');
    }

    /**
     * PBI-09: Driver can mark arrival at the active school stop.
     */
    public function test_driver_can_mark_arrival_at_active_stop(): void
    {
        $stop = $this->inProgressTrip->stops()->first();

        $response = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.stops.arrive', [$this->inProgressTrip, $stop]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'stop_status' => 'ARRIVED_AWAITING_CONFIRMATION',
        ]);

        $stop->refresh();
        $this->assertEquals(TripStopStatus::ARRIVED_AWAITING_CONFIRMATION, $stop->status);
        $this->assertNotNull($stop->arrived_at);

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'trip_stop.arrived',
            'auditable_id' => $stop->id,
        ]);
    }
}
