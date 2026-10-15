<?php

namespace Tests\Feature;

use App\Enums\FoodBatchStatus;
use App\Enums\IncidentCategory;
use App\Enums\IncidentStatus;
use App\Enums\SchoolReceiptStatus;
use App\Enums\TrayReturnStatus;
use App\Enums\TripStatus;
use App\Enums\TripStopStatus;
use App\Enums\TripType;
use App\Enums\UserRole;
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
use App\Services\TrayReturnService;
use Carbon\Carbon;
use Database\Seeders\MbgTrackBaselineSeeder;
use Database\Seeders\MenuBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

class Pbi12ActualOperatorWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Sppg $sppg;
    protected School $schoolA;
    protected School $schoolB;
    protected User $kepalaSppg;
    protected User $kurir;
    protected User $asisten;
    protected User $picSchoolA;
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
        $this->schoolA = School::where('school_code', 'SCH-SDN-01')->firstOrFail();
        $this->schoolB = School::where('school_code', 'SCH-SMPN-02')->firstOrFail();

        $this->kepalaSppg = User::where('role', UserRole::KEPALA_SPPG)
            ->where('sppg_id', $this->sppg->id)
            ->firstOrFail();

        $this->kurir = User::where('role', UserRole::KURIR)
            ->where('sppg_id', $this->sppg->id)
            ->firstOrFail();

        $this->asisten = User::where('role', UserRole::ASISTEN_LAPANGAN)
            ->where('sppg_id', $this->sppg->id)
            ->firstOrFail();

        $this->picSchoolA = User::where('role', UserRole::PIC_SEKOLAH)
            ->where('school_id', $this->schoolA->id)
            ->firstOrFail();

        $this->vehicle = Vehicle::where('sppg_id', $this->sppg->id)
            ->where('is_active', true)
            ->firstOrFail();

        $this->menu = Menu::firstOrFail();

        $this->plan = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->menu->id,
            'service_date' => Carbon::today(),
            'target_portions' => 300,
            'status' => 'COMPLETED',
            'created_by' => $this->kepalaSppg->id,
        ]);

        $this->batch = FoodBatch::create([
            'production_plan_id' => $this->plan->id,
            'menu_id' => $this->menu->id,
            'batch_code' => 'BATCH-OP-' . uniqid(),
            'portion_category' => 'REGULER',
            'planned_portions' => 300,
            'actual_portions' => 300,
            'status' => FoodBatchStatus::PACKED,
            'created_by' => $this->kepalaSppg->id,
        ]);
    }

    /**
     * Create initial delivered trays at school to simulate container in-use inventory.
     */
    protected function createSchoolDeliveredTrays(int $containers = 50): TrayReturn
    {
        // Prior delivery trip context from earlier delivery
        $priorTrip = Trip::create([
            'trip_code' => 'TRIP-HIST-' . uniqid(),
            'sppg_id' => $this->sppg->id,
            'driver_id' => $this->kurir->id,
            'vehicle_id' => $this->vehicle->id,
            'service_date' => Carbon::yesterday(),
            'trip_type' => TripType::DELIVERY,
            'status' => TripStatus::COMPLETED,
            'created_by' => $this->kepalaSppg->id,
        ]);

        $priorStop = TripStop::create([
            'trip_id' => $priorTrip->id,
            'school_id' => $this->schoolA->id,
            'sequence_number' => 1,
            'planned_portions' => 300,
            'loaded_portions' => 300,
            'status' => TripStopStatus::COMPLETED,
        ]);

        $serviceRecord = SchoolServiceRecord::create([
            'trip_stop_id' => $priorStop->id,
            'recipient_name' => 'Ibu Guru PIC',
            'recipient_role' => 'PIC Sekolah',
            'received_portions' => 300,
            'expected_portions' => 300,
            'damaged_portions' => 0,
            'shortage_portions' => 0,
            'status' => SchoolReceiptStatus::MEAL_COMPLETED,
            'received_at' => Carbon::yesterday()->setTime(11, 30),
            'meal_completed_at' => Carbon::yesterday()->setTime(12, 30),
        ]);

        $trayService = app(TrayReturnService::class);
        return $trayService->initiateForRecord($serviceRecord, $containers, true, 'MAN-BOX-50');
    }

    /**
     * Test complete real operator workflow without manual Trip/TripStop fixtures:
     * 1. PIC Sekolah reports ready via POST /school/tray-returns/{tray}/ready
     * 2. Kepala SPPG creates PICKUP trip via POST /head/distribution/trips
     * 3. Kepala SPPG assigns ready tray via POST /head/distribution/tray-returns/{tray}/assign
     * 4. Courier checks vehicle -> Kepala approves dispatch -> Courier starts trip
     * 5. Courier records pickup with discrepancy via POST /driver/tray-returns/{tray}/pickup
     * 6. Asisten Lapangan sees in A03 incoming queue and receives at depot via POST /production/tray-returns/{tray}/receive
     * 7. Immutability re-entry test: differing count rejected with 409 Conflict
     * 8. Incident traceability on C01
     */
    public function test_complete_real_operator_reverse_logistics_workflow(): void
    {
        // ---------------------------------------------------------------------
        // STEP 1: PIC Sekolah reports 50 trays ready for pickup via HTTP endpoint
        // ---------------------------------------------------------------------
        $trayReturn = $this->createSchoolDeliveredTrays(50);
        $this->assertEquals(TrayReturnStatus::IN_USE, $trayReturn->status);

        $readyResponse = $this->actingAs($this->picSchoolA)->post(route('school.trays.ready', $trayReturn), [
            'ready_count' => 50,
            'damaged_count' => 0,
            'notes' => '50 ompreng siap diambil di pos keamanan sekolah',
        ]);
        $readyResponse->assertRedirect();

        $trayReturn->refresh();
        $this->assertEquals(TrayReturnStatus::READY_FOR_PICKUP, $trayReturn->status);
        $this->assertEquals(50, $trayReturn->ready_count);
        $this->assertNotNull($trayReturn->ready_at);

        // ---------------------------------------------------------------------
        // STEP 2: Kepala SPPG visits K05 and creates a PICKUP-only trip via endpoint
        // ---------------------------------------------------------------------
        $k05View = $this->actingAs($this->kepalaSppg)->get(route('head.distribution.index'));
        $k05View->assertOk();
        $k05View->assertSee('Ompreng Siap Jemput');
        $k05View->assertSee($this->schoolA->name);

        $tripPayload = [
            'trip_type' => 'PICKUP',
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->kurir->id,
            'planned_departure_at' => Carbon::today()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'stops' => [
                [
                    'school_id' => $this->schoolA->id,
                    'sequence_number' => 1,
                    'stop_type' => 'PICKUP',
                ],
            ],
        ];

        $createTripResponse = $this->actingAs($this->kepalaSppg)
            ->post(route('head.distribution.trips.store'), $tripPayload);

        $createTripResponse->assertRedirect();
        $createTripResponse->assertSessionHas('success');

        // Verify Trip model created by service
        $pickupTrip = Trip::where('driver_id', $this->kurir->id)
            ->whereDate('service_date', Carbon::today())
            ->firstOrFail();

        $this->assertEquals(TripType::PICKUP, $pickupTrip->trip_type);
        $this->assertTrue($pickupTrip->loading_verified, 'PICKUP trip departs empty so loading_verified must be true upon creation.');
        $this->assertCount(1, $pickupTrip->stops);

        $pickupStop = $pickupTrip->stops->first();
        $this->assertEquals('PICKUP', $pickupStop->stop_type);
        $this->assertEquals(0, $pickupStop->planned_portions);
        $this->assertEquals(0, $pickupStop->loaded_portions);

        // ---------------------------------------------------------------------
        // STEP 3: Kepala SPPG assigns ready tray return to the pickup stop
        // ---------------------------------------------------------------------
        $assignResponse = $this->actingAs($this->kepalaSppg)
            ->post(route('head.distribution.trays.assign', $trayReturn), [
                'pickup_trip_stop_id' => $pickupStop->id,
            ]);

        $assignResponse->assertRedirect();
        $trayReturn->refresh();
        $this->assertEquals(TrayReturnStatus::PICKUP_ASSIGNED, $trayReturn->status);
        $this->assertEquals($pickupStop->id, $trayReturn->pickup_trip_stop_id);

        // ---------------------------------------------------------------------
        // STEP 4: Courier Pre-trip Inspection & Kepala SPPG Dispatch Authorization
        // ---------------------------------------------------------------------
        // Courier performs vehicle check
        $checkResponse = $this->actingAs($this->kurir)
            ->post(route('driver.trips.vehicle-check', $pickupTrip), [
                'passed' => true,
                'notes' => 'Armada bersih, siap penjemputan wadah',
            ]);
        $checkResponse->assertRedirect();

        $pickupTrip->refresh();
        $this->assertTrue($pickupTrip->vehicle_check_passed);

        // Kepala SPPG approves dispatch
        $approveResponse = $this->actingAs($this->kepalaSppg)
            ->post(route('head.distribution.trips.approve-dispatch', $pickupTrip), [
                'notes' => 'Disetujui untuk rute penjemputan',
            ]);
        $approveResponse->assertRedirect();

        $pickupTrip->refresh();
        $this->assertTrue($pickupTrip->dispatch_approved);
        $this->assertEquals(TripStatus::READY, $pickupTrip->status);

        // Courier starts the trip
        $startResponse = $this->actingAs($this->kurir)
            ->post(route('driver.trips.start', $pickupTrip));
        $startResponse->assertRedirect();

        $pickupTrip->refresh();
        $this->assertEquals(TripStatus::IN_PROGRESS, $pickupTrip->status);

        // ---------------------------------------------------------------------
        // STEP 5: Courier visits D05 and records physical pickup with discrepancy
        // ---------------------------------------------------------------------
        $d05Response = $this->actingAs($this->kurir)->get(route('driver.pickups.index'));
        $d05Response->assertOk();
        $d05Response->assertSee($this->schoolA->name);

        // Driver finds only 45 of 50 ready trays (5 missing at school)
        $pickupResponse = $this->actingAs($this->kurir)
            ->post(route('driver.tray-returns.pickup', $trayReturn), [
                'picked_up_count' => 45,
                'notes' => '5 wadah tidak ditemukan di sekolah saat kurir tiba',
            ]);
        $pickupResponse->assertRedirect(route('driver.pickups.index'));

        $trayReturn->refresh();
        $this->assertEquals(TrayReturnStatus::DISCREPANCY, $trayReturn->status);
        $this->assertEquals(45, $trayReturn->picked_up_count);
        $this->assertNotNull($trayReturn->picked_up_at);
        $this->assertNull($trayReturn->returned_at);

        // Verify discrepancy incident automatically raised
        $this->assertDatabaseHas('incidents', [
            'subject_type' => TrayReturn::class,
            'subject_id' => $trayReturn->id,
            'category' => IncidentCategory::TRAY_DISCREPANCY->value,
            'status' => IncidentStatus::OPEN->value,
            'reported_by' => $this->kurir->id,
        ]);

        // ---------------------------------------------------------------------
        // STEP 6: Asisten Lapangan inspects Page A03 and receives trays at depot
        // ---------------------------------------------------------------------
        $a03Response = $this->actingAs($this->asisten)->get(route('production.logistics.index'));
        $a03Response->assertOk();

        // Must appear in incomingReturns queue (in transit from courier)
        $incomingReturns = $a03Response->viewData('incomingReturns');
        $this->assertTrue($incomingReturns->contains('id', $trayReturn->id));

        // Depot reception: Asisten receives 45 returned at SPPG depot
        $receiveResponse = $this->actingAs($this->asisten)
            ->post(route('production.tray-returns.receive', $trayReturn), [
                'returned_count' => 45,
                'damaged_count' => 0,
                'missing_count' => 0,
                'notes' => '45 wadah fisik diterima lengkap di gudang',
            ]);
        $receiveResponse->assertRedirect(route('production.logistics.index'));

        $trayReturn->refresh();
        $this->assertNotNull($trayReturn->returned_at);
        $this->assertEquals(45, $trayReturn->returned_count);

        // Subsequent view of A03 shows it moved to completedReturns
        $a03After = $this->actingAs($this->asisten)->get(route('production.logistics.index'));
        $incomingAfter = $a03After->viewData('incomingReturns');
        $completedAfter = $a03After->viewData('completedReturns');
        $this->assertFalse($incomingAfter->contains('id', $trayReturn->id));
        $this->assertTrue($completedAfter->contains('id', $trayReturn->id));

        // ---------------------------------------------------------------------
        // STEP 7: Immutability Enforcement (Gate D)
        // ---------------------------------------------------------------------
        // Idempotent retry with identical counts succeeds safely
        $retryResponse = $this->actingAs($this->asisten)
            ->post(route('production.tray-returns.receive', $trayReturn), [
                'returned_count' => 45,
                'damaged_count' => 0,
                'missing_count' => 0,
                'notes' => '45 wadah fisik diterima lengkap di gudang',
            ]);
        $retryResponse->assertRedirect(route('production.logistics.index'));

        // Attempt to alter finalized count from 45 to 50 via ordinary receive must be rejected with 409
        $tamperResponse = $this->actingAs($this->asisten)
            ->post(route('production.tray-returns.receive', $trayReturn), [
                'returned_count' => 50,
                'damaged_count' => 0,
                'missing_count' => 0,
            ]);
        $tamperResponse->assertStatus(409);

        // ---------------------------------------------------------------------
        // STEP 8: Cross-role visibility on C01 (Incident Control)
        // ---------------------------------------------------------------------
        $c01Response = $this->actingAs($this->kepalaSppg)->get(route('incidents.index'));
        $c01Response->assertOk();
        $c01Response->assertSee('5 wadah tidak ditemukan di sekolah');

        // ---------------------------------------------------------------------
        // STEP 9: Edge Case Validations on PICKUP Trips
        // ---------------------------------------------------------------------
        // Asisten cannot access Head endpoint (RBAC 403)
        $unauthorizedResponse = $this->actingAs($this->asisten)
            ->post(route('head.distribution.trips.verify-loading', $pickupTrip), []);
        $unauthorizedResponse->assertForbidden();

        // Loading verification cannot be performed on a PICKUP trip (Domain Invariant)
        try {
            app(\App\Services\TripDispatchService::class)->verifyLoading(
                $pickupTrip,
                ['items' => [1 => 10]],
                $this->kepalaSppg
            );
            $this->fail('Expected ValidationException when verifying loading on a PICKUP trip.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('trip', $e->errors());
            $this->assertStringContainsString('PICKUP', $e->errors()['trip'][0]);
        }
    }

    /**
     * Test domain guard: Creating a PICKUP trip with DELIVERY food items is rejected.
     */
    public function test_pickup_trip_rejects_delivery_food_items(): void
    {
        $payload = [
            'trip_type' => 'PICKUP',
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->kurir->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA->id,
                    'sequence_number' => 1,
                    'stop_type' => 'DELIVERY', // Incompatible with PICKUP trip
                    'items' => [
                        [
                            'delivery_item_id' => '00000000-0000-0000-0000-000000000001',
                            'planned_portions' => 100,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->kepalaSppg)
            ->post(route('head.distribution.trips.store'), $payload);

        $response->assertSessionHasErrors(['stops.0.stop_type']);
    }

    /**
     * Test domain guard: Assigning ready trays to a trip of a different SPPG is forbidden.
     */
    public function test_cannot_assign_trays_to_cross_tenant_trip(): void
    {
        $trayReturn = $this->createSchoolDeliveredTrays(30);

        // Other SPPG Kepala
        $otherSppg = Sppg::create([
            'name' => 'SPPG Secondary Unit',
            'code' => 'SPPG-SUB-02',
            'address' => 'Jl. Kenjeran No. 88, Surabaya',
            'latitude' => -7.2400,
            'longitude' => 112.7800,
            'is_active' => true,
        ]);

        $otherKepala = User::factory()->create([
            'sppg_id' => $otherSppg->id,
            'role' => UserRole::KEPALA_SPPG,
            'name' => 'Kepala SPPG 2',
            'email' => 'kepala2@mbgtrack.test',
        ]);

        $response = $this->actingAs($otherKepala)
            ->post(route('head.distribution.trays.assign', $trayReturn), [
                'trip_id' => 1,
            ]);

        $response->assertForbidden();
    }
}
