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
use App\Models\TripStop;
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

    /**
     * Critical Gate A: Active trip navigation page does NOT auto-request GPS on load.
     * Requires explicit driver action via "Mulai Bagikan Lokasi" button.
     */
    public function test_active_trip_page_does_not_request_gps_automatically(): void
    {
        $response = $this->actingAs($this->driverA)
            ->get(route('driver.trips.active', $this->inProgressTrip));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Must have explicit consent button
        $this->assertStringContainsString('Mulai Bagikan Lokasi', $content);
        $this->assertStringContainsString('Berbagi Lokasi GPS Perjalanan', $content);
        $this->assertStringContainsString('Menunggu Tindakan Kurir (Belum Aktif)', $content);

        // Must NOT call startTracking() automatically on load / DOMContentLoaded
        $this->assertStringNotContainsString('// Automatically attempt geolocation sharing on load', $content);
        $this->assertStringContainsString('btnToggleGps?.addEventListener(\'click\'', $content);
        $this->assertStringContainsString('startTracking();', $content);
    }

    /**
     * Critical Gate B: Out-of-order delayed GPS telemetry does NOT roll back Trip cached latest location.
     */
    public function test_out_of_order_stale_telemetry_does_not_overwrite_newer_cached_location(): void
    {
        $timeNewer = Carbon::now()->subMinutes(2);
        $timeOlder = Carbon::now()->subMinutes(10);

        // 1. Submit newer telemetry (12:05)
        $res1 = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.telemetry', $this->inProgressTrip), [
                'latitude' => -7.288543,
                'longitude' => 112.735123,
                'accuracy' => 10.0,
                'recorded_at' => $timeNewer->toIso8601String(),
            ]);
        $res1->assertStatus(200);

        $this->inProgressTrip->refresh();
        $this->assertEquals(-7.288543, (float) $this->inProgressTrip->last_latitude);
        $this->assertEquals(112.735123, (float) $this->inProgressTrip->last_longitude);

        // 2. Submit older delayed network telemetry (11:57)
        $res2 = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.telemetry', $this->inProgressTrip), [
                'latitude' => -7.291234,
                'longitude' => 112.721456,
                'accuracy' => 15.0,
                'recorded_at' => $timeOlder->toIso8601String(),
            ]);
        $res2->assertStatus(200);

        // Verify that GpsLocation has both records for audit trail
        $this->assertEquals(2, GpsLocation::where('trip_id', $this->inProgressTrip->id)->count());

        // But Trip latest cached position MUST NOT be rolled back to older point!
        $this->inProgressTrip->refresh();
        $this->assertEquals(-7.288543, (float) $this->inProgressTrip->last_latitude, 'Trip last_latitude must not roll back to older telemetry');
        $this->assertEquals(112.735123, (float) $this->inProgressTrip->last_longitude, 'Trip last_longitude must not roll back to older telemetry');
        $this->assertEquals($timeNewer->toIso8601String(), $this->inProgressTrip->last_location_at->toIso8601String(), 'Trip last_location_at must remain at the newer timestamp');
    }

    /**
     * Critical Gate C: Telemetry with negative accuracy is rejected.
     */
    public function test_negative_accuracy_rejected(): void
    {
        $response = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.telemetry', $this->inProgressTrip), [
                'latitude' => -7.288543,
                'longitude' => 112.735123,
                'accuracy' => -5.0,
                'recorded_at' => Carbon::now()->toIso8601String(),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['accuracy']);
    }

    /**
     * Critical Gate C: Telemetry with negative speed is rejected.
     */
    public function test_negative_speed_rejected(): void
    {
        $response = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.telemetry', $this->inProgressTrip), [
                'latitude' => -7.288543,
                'longitude' => 112.735123,
                'accuracy' => 10.0,
                'speed' => -2.5,
                'recorded_at' => Carbon::now()->toIso8601String(),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['speed']);
    }

    /**
     * Critical Gate C: Telemetry with out-of-range heading is rejected.
     */
    public function test_invalid_heading_rejected(): void
    {
        $response = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.telemetry', $this->inProgressTrip), [
                'latitude' => -7.288543,
                'longitude' => 112.735123,
                'accuracy' => 10.0,
                'heading' => 450.0,
                'recorded_at' => Carbon::now()->toIso8601String(),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['heading']);
    }

    /**
     * Critical Gate C: Coordinate teleportation/outlier jump is rejected.
     */
    public function test_telemetry_outlier_jump_rejected(): void
    {
        $time1 = Carbon::now()->subSeconds(10);
        $time2 = Carbon::now()->subSeconds(5);

        // 1. Initial valid location in Wonokromo, Surabaya
        $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.telemetry', $this->inProgressTrip), [
                'latitude' => -7.288543,
                'longitude' => 112.735123,
                'accuracy' => 10.0,
                'recorded_at' => $time1->toIso8601String(),
            ]);

        // 2. 5 seconds later, impossible jump to Jakarta (-6.2000, 106.8166) ~ 650 km
        $response = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.telemetry', $this->inProgressTrip), [
                'latitude' => -6.200000,
                'longitude' => 106.816667,
                'accuracy' => 10.0,
                'recorded_at' => $time2->toIso8601String(),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['coordinates']);
    }

    /**
     * Critical Gate C: Duplicate telemetry payload is idempotent without duplicating records.
     */
    public function test_duplicate_telemetry_payload_is_idempotent(): void
    {
        $time = Carbon::now()->subSeconds(20);
        $payload = [
            'latitude' => -7.288543,
            'longitude' => 112.735123,
            'accuracy' => 10.0,
            'recorded_at' => $time->toIso8601String(),
        ];

        $res1 = $this->actingAs($this->driverA)->postJson(route('driver.trips.telemetry', $this->inProgressTrip), $payload);
        $res1->assertStatus(200);

        $res2 = $this->actingAs($this->driverA)->postJson(route('driver.trips.telemetry', $this->inProgressTrip), $payload);
        $res2->assertStatus(200);

        $this->assertEquals(1, GpsLocation::where('trip_id', $this->inProgressTrip->id)->count());
    }

    /**
     * Critical Gate D: Stop 2 cannot be marked ARRIVED before Stop 1 has arrived.
     */
    public function test_subsequent_stop_cannot_arrive_before_first_stop(): void
    {
        $stop2 = TripStop::create([
            'trip_id' => $this->inProgressTrip->id,
            'school_id' => $this->schoolA2->id,
            'sequence_number' => 2,
            'planned_portions' => 150,
            'status' => TripStopStatus::PENDING,
        ]);

        $stops = $this->inProgressTrip->stops()->orderBy('sequence_number')->get();
        $stop1 = $stops[0];
        $this->assertEquals(1, $stop1->sequence_number);
        $this->assertEquals(2, $stop2->sequence_number);

        // Attempt marking stop 2 arrived while stop 1 is still EN_ROUTE / PENDING
        $response = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.stops.arrive', [$this->inProgressTrip, $stop2]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['stop']);
    }

    /**
     * Critical Gate D: Double-submit arrival on active stop is idempotent.
     */
    public function test_double_submit_arrival_is_idempotent(): void
    {
        $stop1 = $this->inProgressTrip->stops()->orderBy('sequence_number')->first();

        $res1 = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.stops.arrive', [$this->inProgressTrip, $stop1]));
        $res1->assertStatus(200);

        $res2 = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.stops.arrive', [$this->inProgressTrip, $stop1]));
        $res2->assertStatus(200);

        $logsCount = ActivityLog::where('event', 'trip_stop.arrived')
            ->where('auditable_id', $stop1->id)
            ->count();
        $this->assertEquals(1, $logsCount, 'Idempotent arrival must not create duplicate activity logs.');
    }

    /**
     * Critical Gate 8: Public map data uses actual model updated_at timestamp, not request time.
     */
    public function test_public_map_data_uses_actual_updated_at_timestamp_not_now(): void
    {
        $expectedTime = $this->inProgressTrip->updated_at->toIso8601String();

        $response = $this->getJson(route('public.trips.map-data', $this->inProgressTrip->trip_code));

        $response->assertStatus(200);
        $this->assertEquals($expectedTime, $response->json('last_updated'));
    }

    /**
     * Requirement C: Driver can explicitly stop GPS sharing and server synchronizes stopped status.
     */
    public function test_driver_can_explicitly_stop_gps_telemetry_sharing(): void
    {
        // 1. Submit initial telemetry
        $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.telemetry', $this->inProgressTrip), [
                'latitude' => -7.288543,
                'longitude' => 112.735123,
                'accuracy' => 10.0,
                'recorded_at' => Carbon::now()->toIso8601String(),
            ])
            ->assertStatus(200);

        // 2. Stop sharing
        $response = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.telemetry.stop', $this->inProgressTrip));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'tracking_status' => 'SHARING_STOPPED',
        ]);

        $this->inProgressTrip->refresh();
        $this->assertEquals('SHARING_STOPPED', $this->inProgressTrip->tracking_status);
        $this->assertNotNull($this->inProgressTrip->tracking_ended_at);
        $this->assertEquals(-7.288543, (float) $this->inProgressTrip->last_latitude, 'Last latitude preserved on stop');
        $this->assertEquals(112.735123, (float) $this->inProgressTrip->last_longitude, 'Last longitude preserved on stop');

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'trip.tracking_stopped',
            'auditable_id' => $this->inProgressTrip->id,
        ]);
    }

    /**
     * Requirement C: Stopped GPS does not retain false ONLINE status on monitoring dashboard.
     */
    public function test_stopped_gps_does_not_retain_online_status_on_monitoring_dashboard(): void
    {
        $trackingService = app(GpsTrackingService::class);

        // Telemetry recorded 10 seconds ago, but kurir stopped sharing
        $this->inProgressTrip->update([
            'last_latitude' => -7.288543,
            'last_longitude' => 112.735123,
            'last_accuracy_m' => 8.0,
            'last_location_at' => Carbon::now()->subSeconds(10),
            'tracking_status' => 'SHARING_STOPPED',
            'tracking_ended_at' => Carbon::now()->subSeconds(5),
        ]);

        $status = $trackingService->getDriverTrackingStatus($this->inProgressTrip);
        $this->assertEquals('SHARING_STOPPED', $status['status']);
        $this->assertFalse($status['is_online'], 'Stopped tracking must NEVER report is_online true');
        $this->assertTrue($status['is_stopped']);

        // Check map-data endpoint as Kepala SPPG
        $response = $this->actingAs($this->kepalaA)
            ->getJson(route('trips.map-data', $this->inProgressTrip));

        $response->assertStatus(200);
        $telemetry = $response->json('driver_telemetry');
        $this->assertNotNull($telemetry);
        $this->assertEquals('SHARING_STOPPED', $telemetry['status']);
        $this->assertFalse($telemetry['is_online']);
    }

    /**
     * Requirement C: Driver submitting new telemetry after stop resets ONLINE status.
     */
    public function test_driver_submitting_new_telemetry_resets_online_status(): void
    {
        $this->inProgressTrip->update([
            'last_location_at' => Carbon::now()->subMinutes(5),
            'tracking_status' => 'SHARING_STOPPED',
            'tracking_ended_at' => Carbon::now()->subMinutes(5),
        ]);

        $response = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.telemetry', $this->inProgressTrip), [
                'latitude' => -7.289000,
                'longitude' => 112.736000,
                'accuracy' => 9.0,
                'recorded_at' => Carbon::now()->toIso8601String(),
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'tracking_status' => 'ONLINE',
        ]);

        $this->inProgressTrip->refresh();
        $this->assertEquals('ONLINE', $this->inProgressTrip->tracking_status);
        $this->assertNull($this->inProgressTrip->tracking_ended_at, 'tracking_ended_at must be reset upon resumption');
    }

    /**
     * Requirement D: Terminal stop cannot be re-arrived.
     */
    public function test_terminal_stop_cannot_be_re_arrived(): void
    {
        $stop1 = $this->inProgressTrip->stops()->orderBy('sequence_number')->first();
        $stop1->update([
            'status' => TripStopStatus::COMPLETED,
            'handed_over_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.stops.arrive', [$this->inProgressTrip, $stop1]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['stop']);
    }

    /**
     * Requirement D: Stop 2 cannot arrive while Stop 1 is awaiting handover confirmation (PBI-10 dependency).
     */
    public function test_subsequent_stop_requires_prior_stop_to_complete_handover(): void
    {
        $stop2 = TripStop::create([
            'trip_id' => $this->inProgressTrip->id,
            'school_id' => $this->schoolA2->id,
            'sequence_number' => 2,
            'planned_portions' => 150,
            'status' => TripStopStatus::PENDING,
        ]);

        $stop1 = $this->inProgressTrip->stops()->where('sequence_number', 1)->first();
        $stop1->update(['status' => TripStopStatus::ARRIVED_AWAITING_CONFIRMATION]);

        // 1. Attempting Stop 2 when Stop 1 has arrived but NOT handed over must be rejected
        $res1 = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.stops.arrive', [$this->inProgressTrip, $stop2]));

        $res1->assertStatus(422);
        $res1->assertJsonValidationErrors(['stop']);

        // 2. Once Stop 1 completes handover (PBI-10), Stop 2 can mark arrival
        $stop1->update(['status' => TripStopStatus::HANDED_OVER]);

        $res2 = $this->actingAs($this->driverA)
            ->postJson(route('driver.trips.stops.arrive', [$this->inProgressTrip, $stop2]));

        $res2->assertStatus(200);
        $stop2->refresh();
        $this->assertEquals(TripStopStatus::ARRIVED_AWAITING_CONFIRMATION, $stop2->status);
    }

    /**
     * Requirement E: Isolated dev diagnostic route rejected in production environment.
     */
    public function test_dev_map_test_route_is_rejected_in_production_environment(): void
    {
        $this->app['env'] = 'production';

        $response = $this->get('/dev/map-test');
        $response->assertStatus(404);
    }

    /**
     * Requirement F: Null released portions strictly returns 0, never planned portions.
     */
    public function test_released_portions_null_does_not_fallback_to_planned_portions(): void
    {
        $stop = $this->inProgressTrip->stops()->first();
        $item = $stop->deliveryItems()->first();

        // Simulate unreleased item (QC pending)
        $item->update([
            'planned_portions' => 250,
            'released_portions' => null,
        ]);

        $stop->refresh();
        $this->assertEquals(0, $stop->total_released_portions, 'total_released_portions must be 0 when released_portions is null');
    }

    /**
     * Requirement F: Public map data reflects newest stop arrival or location update timestamp.
     */
    public function test_public_last_updated_reflects_latest_stop_or_gps_change(): void
    {
        $stop = $this->inProgressTrip->stops()->first();
        $laterTime = Carbon::now()->addMinutes(15);
        $stop->update(['arrived_at' => $laterTime]);

        $response = $this->getJson(route('public.trips.map-data', $this->inProgressTrip->trip_code));

        $response->assertStatus(200);
        $this->assertEquals($laterTime->toIso8601String(), $response->json('last_updated'));
    }
}

