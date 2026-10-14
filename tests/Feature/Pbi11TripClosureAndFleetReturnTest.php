<?php

namespace Tests\Feature;

use App\Enums\FoodBatchStatus;
use App\Enums\IncidentCategory;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Enums\MenuStatus;
use App\Enums\SchoolReceiptStatus;
use App\Enums\TrayReturnStatus;
use App\Enums\TripStatus;
use App\Enums\TripStopStatus;
use App\Enums\TripType;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\DeliveryItem;
use App\Models\FoodBatch;
use App\Models\Incident;
use App\Models\Menu;
use App\Models\ProductionPlan;
use App\Models\School;
use App\Models\SchoolServiceRecord;
use App\Models\Sppg;
use App\Models\TrayReturn;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\TripClosureService;
use Carbon\Carbon;
use Database\Seeders\MbgTrackBaselineSeeder;
use Database\Seeders\MenuBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Pbi11TripClosureAndFleetReturnTest extends TestCase
{
    use RefreshDatabase;

    protected Sppg $sppg;
    protected Sppg $otherSppg;
    protected School $schoolA;
    protected School $schoolB;
    protected User $driver;
    protected User $otherDriver;
    protected User $kepalaSppg;
    protected User $otherKepalaSppg;
    protected User $picSchool;
    protected Vehicle $vehicle;
    protected Menu $menu;
    protected ProductionPlan $plan;
    protected FoodBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->seed(MbgTrackBaselineSeeder::class);
        $this->seed(MenuBaselineSeeder::class);

        $this->sppg = Sppg::where('code', 'SPPG-SUB-01')->firstOrFail();
        $this->otherSppg = Sppg::where('code', '!=', 'SPPG-SUB-01')->first() ?? Sppg::create([
            'name' => 'SPPG Secondary Unit',
            'code' => 'SPPG-SUB-02',
            'address' => 'Jl. Kenjeran No. 88, Surabaya',
            'latitude' => -7.2400,
            'longitude' => 112.7800,
            'is_active' => true,
        ]);

        $this->schoolA = School::where('school_code', 'SCH-SDN-01')->firstOrFail();
        $this->schoolB = School::where('school_code', 'SCH-SMPN-02')->firstOrFail();

        $this->driver = User::where('email', 'kurir@mbgtrack.test')->firstOrFail();
        $this->otherDriver = User::factory()->create([
            'sppg_id' => $this->sppg->id,
            'role' => UserRole::KURIR,
            'name' => 'Budi Driver Lain',
            'email' => 'other.driver@mbgtrack.test',
        ]);

        $this->kepalaSppg = User::where('role', UserRole::KEPALA_SPPG)
            ->where('sppg_id', $this->sppg->id)
            ->firstOrFail();

        $this->otherKepalaSppg = User::factory()->create([
            'sppg_id' => $this->otherSppg->id,
            'role' => UserRole::KEPALA_SPPG,
            'name' => 'Kepala SPPG 02',
            'email' => 'kepala.sppg02@mbgtrack.test',
        ]);

        $this->picSchool = User::where('email', 'pic.sdn01@mbgtrack.test')->firstOrFail();

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
            'target_portions' => 800,
            'status' => \App\Enums\ProductionPlanStatus::COMPLETED,
            'created_by' => $this->kepalaSppg->id,
        ]);

        $this->batch = FoodBatch::create([
            'production_plan_id' => $this->plan->id,
            'menu_id' => $this->menu->id,
            'batch_code' => 'BATCH-PBI11-' . uniqid(),
            'portion_category' => 'REGULER',
            'planned_portions' => 800,
            'actual_portions' => 800,
            'status' => FoodBatchStatus::PACKED,
            'created_by' => $this->kepalaSppg->id,
        ]);
    }

    /**
     * Helper to create an active trip with specified stops.
     */
    protected function createActiveTrip(array $stopStatuses): Trip
    {
        $trip = Trip::create([
            'sppg_id' => $this->sppg->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'service_date' => Carbon::today(),
            'trip_type' => TripType::DELIVERY,
            'status' => TripStatus::IN_PROGRESS,
            'created_by' => $this->kepalaSppg->id,
            'trip_code' => 'TRIP-PBI11-' . uniqid(),
            'started_at' => Carbon::now()->subMinutes(45),
            'tracking_status' => 'ONLINE',
            'active_tracking_session_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $schools = [$this->schoolA, $this->schoolB];

        foreach ($stopStatuses as $idx => $status) {
            $school = $schools[$idx % count($schools)];

            $portionReq = \App\Models\DailyPortionRequest::firstOrCreate(
                ['school_id' => $school->id, 'service_date' => Carbon::today()],
                [
                    'sppg_id' => $this->sppg->id,
                    'registered_count' => 300,
                    'requested_portions' => 300,
                    'status' => \App\Enums\PortionRequestStatus::CONFIRMED,
                    'created_by' => $this->picSchool->id,
                    'confirmed_at' => now(),
                ]
            );

            $alloc = \App\Models\ProductionAllocation::firstOrCreate(
                [
                    'production_plan_id' => $this->plan->id,
                    'portion_request_id' => $portionReq->id,
                ],
                [
                    'school_id' => $school->id,
                    'allocated_portions' => 300,
                ]
            );

            $stop = TripStop::create([
                'trip_id' => $trip->id,
                'school_id' => $school->id,
                'sequence_number' => $idx + 1,
                'status' => $status,
                'planned_portions' => 300,
                'loaded_portions' => 300,
                'arrived_at' => in_array($status, [TripStopStatus::ARRIVED, TripStopStatus::COMPLETED, TripStopStatus::HANDED_OVER], true) ? Carbon::now()->subMinutes(20) : null,
                'completed_at' => in_array($status, [TripStopStatus::COMPLETED, TripStopStatus::HANDED_OVER], true) ? Carbon::now()->subMinutes(15) : null,
            ]);

            DeliveryItem::create([
                'trip_stop_id' => $stop->id,
                'food_batch_id' => $this->batch->id,
                'production_allocation_id' => $alloc->id,
                'planned_portions' => 300,
                'loaded_portions' => 300,
                'delivered_portions' => in_array($status, [TripStopStatus::COMPLETED, TripStopStatus::HANDED_OVER], true) ? 300 : null,
                'status' => 'LOADED',
            ]);
        }

        return $trip;
    }

    /**
     * Test Gate 2: Unhandled stops block fleet return and trip closure.
     */
    public function test_unhandled_delivery_stop_blocks_fleet_return_and_closure(): void
    {
        // Trip with Stop 1 completed but Stop 2 still EN_ROUTE
        $trip = $this->createActiveTrip([
            TripStopStatus::COMPLETED,
            TripStopStatus::EN_ROUTE,
        ]);

        // Attempt return as Driver
        $response = $this->actingAs($this->driver)
            ->post(route('driver.trips.return', $trip));

        $response->assertSessionHasErrors();
        $trip->refresh();
        $this->assertNull($trip->return_started_at);

        // Attempt closure directly
        $completeResponse = $this->actingAs($this->driver)
            ->post(route('driver.trips.complete', $trip));

        $completeResponse->assertSessionHasErrors();
        $trip->refresh();
        $this->assertEquals(TripStatus::IN_PROGRESS, $trip->status);
    }

    /**
     * Test Gate 2: Non-standard terminal stop (EXCEPTION / SKIPPED) allows return without silent conversion.
     */
    public function test_terminal_exception_stop_allows_return_without_altering_exception_status(): void
    {
        // Stop 1 is COMPLETED, Stop 2 has EXCEPTION
        $trip = $this->createActiveTrip([
            TripStopStatus::COMPLETED,
            TripStopStatus::EXCEPTION,
        ]);

        // Kurir initiates return
        $response = $this->actingAs($this->driver)
            ->post(route('driver.trips.return', $trip), [
                'return_notes' => 'Kembali setelah stop 2 gagal akibat banjir.',
            ]);

        $response->assertRedirect(route('driver.trips.active', $trip));
        $trip->refresh();

        $this->assertNotNull($trip->return_started_at);
        $this->assertTrue($trip->isReturning());

        // Check Stop 2 remains EXCEPTION and is NOT silently converted to COMPLETED
        $stop2 = $trip->stops->where('sequence_number', 2)->first();
        $this->assertEquals(TripStopStatus::EXCEPTION, $stop2->status);
    }

    /**
     * Test Gate 3: Fleet return initiation and depot arrival recording with server timestamps and idempotency.
     */
    public function test_fleet_return_and_depot_arrival_workflow(): void
    {
        $trip = $this->createActiveTrip([
            TripStopStatus::HANDED_OVER,
            TripStopStatus::HANDED_OVER,
        ]);

        // 1. Driver initiates return
        $returnResp = $this->actingAs($this->driver)
            ->post(route('driver.trips.return', $trip), [
                'return_notes' => 'Rute aman, berangkat kembali ke SPPG',
            ]);

        $returnResp->assertRedirect(route('driver.trips.active', $trip));
        $trip->refresh();

        $this->assertNotNull($trip->return_started_at);
        $this->assertEquals('Rute aman, berangkat kembali ke SPPG', $trip->return_notes);
        $this->assertTrue($trip->isReturning());

        // Idempotency: Calling return again preserves original return timestamp
        $originalReturnTime = $trip->return_started_at;
        $this->travel(5)->minutes();

        $secondReturnResp = $this->actingAs($this->driver)
            ->post(route('driver.trips.return', $trip));

        $secondReturnResp->assertRedirect(route('driver.trips.active', $trip));
        $trip->refresh();
        $this->assertEquals($originalReturnTime->toIso8601String(), $trip->return_started_at->toIso8601String());

        // 2. Driver records arrival at SPPG depot
        $arriveResp = $this->actingAs($this->driver)
            ->post(route('driver.trips.arrive-depot', $trip));

        $arriveResp->assertRedirect(route('driver.trips.active', $trip));
        $trip->refresh();

        $this->assertNotNull($trip->arrived_at_sppg_at);
        $this->assertTrue($trip->hasArrivedAtDepot());

        // Idempotency: Calling arrive-depot again preserves original arrival timestamp
        $originalArriveTime = $trip->arrived_at_sppg_at;
        $this->travel(5)->minutes();

        $secondArriveResp = $this->actingAs($this->driver)
            ->post(route('driver.trips.arrive-depot', $trip));

        $secondArriveResp->assertRedirect(route('driver.trips.active', $trip));
        $trip->refresh();
        $this->assertEquals($originalArriveTime->toIso8601String(), $trip->arrived_at_sppg_at->toIso8601String());
    }

    /**
     * Test Gate 4: Tray & container reconciliation records counts and raises discrepancy incidents.
     */
    public function test_tray_reconciliation_records_counts_and_creates_incidents_on_discrepancy(): void
    {
        $trip = $this->createActiveTrip([
            TripStopStatus::HANDED_OVER,
        ]);

        // Put trip in arrived state
        $trip->update([
            'return_started_at' => Carbon::now()->subMinutes(20),
            'arrived_at_sppg_at' => Carbon::now()->subMinutes(5),
        ]);

        // 1. Validation: Negative count rejected
        $invalidResp = $this->actingAs($this->driver)
            ->post(route('driver.trips.reconcile-trays', $trip), [
                'trays_returned' => -5,
                'trays_damaged' => 0,
                'trays_missing' => 0,
            ]);

        $invalidResp->assertSessionHasErrors(['trays_returned']);

        // 2. Reconcile with 2 damaged and 1 missing trays
        $validResp = $this->actingAs($this->driver)
            ->post(route('driver.trips.reconcile-trays', $trip), [
                'trays_returned' => 27,
                'trays_damaged' => 2,
                'trays_missing' => 1,
                'notes' => '1 tutup retak dan 1 wadah hilang di SDN 01',
            ]);

        $validResp->assertRedirect(route('driver.trips.active', $trip));
        $trip->refresh();

        $this->assertEquals(27, $trip->total_trays_returned);
        $this->assertEquals(2, $trip->total_trays_damaged);
        $this->assertEquals(1, $trip->total_trays_missing);

        // Verify incident created (Gate 4)
        $incident = Incident::where('subject_type', Trip::class)
            ->where('subject_id', $trip->id)
            ->first();
        $this->assertNotNull($incident);
        $this->assertEquals(IncidentCategory::TRAY_DISCREPANCY, $incident->category);
        $this->assertEquals(IncidentStatus::OPEN, $incident->status);
        $this->assertStringContainsString('Selisih fisik ompreng', $incident->description);
    }

    /**
     * Test Gate 5: Final trip closure completes trip, sets closed_by, terminates GPS session, and prevents telemetry resurrection.
     */
    public function test_final_trip_closure_shuts_down_gps_and_prevents_telemetry_resurrection(): void
    {
        $trip = $this->createActiveTrip([
            TripStopStatus::COMPLETED,
        ]);

        $trip->update([
            'return_started_at' => Carbon::now()->subMinutes(30),
            'arrived_at_sppg_at' => Carbon::now()->subMinutes(10),
            'total_trays_returned' => 30,
            'total_trays_damaged' => 0,
            'total_trays_missing' => 0,
        ]);

        $this->assertEquals('ONLINE', $trip->tracking_status);
        $this->assertNotNull($trip->active_tracking_session_id);

        // Close trip
        $closeResp = $this->actingAs($this->driver)
            ->post(route('driver.trips.complete', $trip), [
                'completion_notes' => 'Perjalanan selesai dengan selamat, armada diparkir di pangkalan SPPG.',
            ]);

        $closeResp->assertRedirect(route('driver.trips.active', $trip));
        $trip->refresh();

        $this->assertEquals(TripStatus::COMPLETED, $trip->status);
        $this->assertNotNull($trip->completed_at);
        $this->assertEquals($this->driver->id, $trip->closed_by);
        $this->assertEquals('OFFLINE', $trip->tracking_status);
        $this->assertNull($trip->active_tracking_session_id);

        // Verify ActivityLog was recorded
        $log = ActivityLog::where('auditable_type', Trip::class)
            ->where('auditable_id', $trip->id)
            ->where('event', 'trip.completed')
            ->first();
        $this->assertNotNull($log);

        // Telemetry resurrection prevention: Attempting to send GPS coordinates to closed trip returns 422
        $telemetryResp = $this->actingAs($this->driver)
            ->postJson(route('driver.trips.telemetry', $trip), [
                'latitude' => -7.2885,
                'longitude' => 112.7385,
                'accuracy' => 10,
                'recorded_at' => Carbon::now()->toIso8601String(),
            ]);

        $telemetryResp->assertStatus(422);
    }

    /**
     * Test Gate 6: Mode B null values (unallocated portions) are preserved on trip closure without converting to zero.
     */
    public function test_mode_b_unallocated_null_values_are_preserved_upon_closure(): void
    {
        $trip = $this->createActiveTrip([
            TripStopStatus::HANDED_OVER,
        ]);

        $stop = $trip->stops->first();

        // Create emergency service record with Mode B acceptance (accepted_portions = null)
        SchoolServiceRecord::create([
            'trip_stop_id' => $stop->id,
            'received_by' => $this->picSchool->id,
            'received_at' => Carbon::now()->subMinutes(15),
            'expected_portions' => 300,
            'received_portions' => 300,
            'status' => SchoolReceiptStatus::RECEIVED,
            'notes' => 'Koneksi seluler terputus, verifikasi fisik darurat Mode B.',
            'created_at' => Carbon::now()->subMinutes(15),
        ]);

        // Keep delivery items delivered_portions = null for Mode B
        $deliveryItem = $stop->deliveryItems->first();
        $deliveryItem->update(['delivered_portions' => null]);

        $trip->update([
            'return_started_at' => Carbon::now()->subMinutes(25),
            'arrived_at_sppg_at' => Carbon::now()->subMinutes(10),
            'total_trays_returned' => 15,
        ]);

        // Check closure readiness detects Mode B unallocated
        $closureService = app(TripClosureService::class);
        $readiness = $closureService->getClosureReadiness($trip);
        $this->assertGreaterThan(0, $readiness['mode_b_unallocated_stops']);

        // Complete the trip
        $response = $this->actingAs($this->driver)
            ->post(route('driver.trips.complete', $trip));

        $response->assertRedirect(route('driver.trips.active', $trip));
        $trip->refresh();
        $this->assertEquals(TripStatus::COMPLETED, $trip->status);

        // CRITICAL CHECK: Verify delivered_portions remains NULL and was NOT coerced to 0
        $deliveryItem->refresh();
        $this->assertNull($deliveryItem->delivered_portions);
    }

    /**
     * Test Gate 7: RBAC & Tenant Isolation.
     */
    public function test_rbac_and_tenant_isolation_for_trip_closure_actions(): void
    {
        $trip = $this->createActiveTrip([
            TripStopStatus::COMPLETED,
        ]);

        $trip->update([
            'return_started_at' => Carbon::now()->subMinutes(20),
            'arrived_at_sppg_at' => Carbon::now()->subMinutes(5),
            'total_trays_returned' => 30,
        ]);

        // 1. Another driver cannot return or close trip (403)
        $this->actingAs($this->otherDriver)
            ->post(route('driver.trips.return', $trip))
            ->assertForbidden();

        $this->actingAs($this->otherDriver)
            ->post(route('driver.trips.complete', $trip))
            ->assertForbidden();

        // 2. PIC Sekolah cannot close trip (403)
        $this->actingAs($this->picSchool)
            ->post(route('driver.trips.complete', $trip))
            ->assertForbidden();

        // 3. Kepala SPPG from other SPPG cannot close trip (403)
        $this->actingAs($this->otherKepalaSppg)
            ->post(route('head.distribution.trips.close', $trip))
            ->assertForbidden();

        // 4. Same tenant Kepala SPPG can close trip administratively
        $headCloseResp = $this->actingAs($this->kepalaSppg)
            ->post(route('head.distribution.trips.close', $trip), [
                'completion_notes' => 'Penutupan administratif resmi oleh Kepala SPPG.',
            ]);

        $headCloseResp->assertRedirect(route('trips.show', $trip));
        $trip->refresh();
        $this->assertEquals(TripStatus::COMPLETED, $trip->status);
        $this->assertEquals($this->kepalaSppg->id, $trip->closed_by);
    }
}
