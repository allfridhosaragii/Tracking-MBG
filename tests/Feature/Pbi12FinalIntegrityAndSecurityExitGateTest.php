<?php

namespace Tests\Feature;

use App\Enums\FoodBatchStatus;
use App\Enums\IncidentCategory;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Enums\MenuStatus;
use App\Enums\PortionRequestStatus;
use App\Enums\ProductionPlanStatus;
use App\Enums\SchoolReceiptStatus;
use App\Enums\TrayReturnStatus;
use App\Enums\TripStatus;
use App\Enums\TripStopStatus;
use App\Enums\TripType;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\DailyPortionRequest;
use App\Models\DeliveryItem;
use App\Models\EvidencePhoto;
use App\Models\FoodBatch;
use App\Models\Incident;
use App\Models\Menu;
use App\Models\ProductionAllocation;
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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class Pbi12FinalIntegrityAndSecurityExitGateTest extends TestCase
{
    use RefreshDatabase;

    protected Sppg $sppgA;
    protected Sppg $sppgB;
    protected School $schoolA1;
    protected School $schoolA2;
    protected School $schoolB1;
    protected User $kepalaSppgA;
    protected User $kepalaSppgB;
    protected User $kurirA;
    protected User $kurirB;
    protected User $asistenA;
    protected User $picSchoolA1;
    protected User $picSchoolA2;
    protected User $picSchoolB1;
    protected Vehicle $vehicleA;
    protected Vehicle $vehicleB;
    protected Menu $menu;
    protected ProductionPlan $planA;
    protected FoodBatch $batchA;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->seed(MbgTrackBaselineSeeder::class);
        $this->seed(MenuBaselineSeeder::class);

        $this->sppgA = Sppg::where('code', 'SPPG-SUB-01')->firstOrFail();
        $this->sppgB = Sppg::where('code', '!=', 'SPPG-SUB-01')->first() ?? Sppg::create([
            'name' => 'SPPG Secondary Unit',
            'code' => 'SPPG-SUB-02',
            'address' => 'Jl. Kenjeran No. 88, Surabaya',
            'latitude' => -7.2400,
            'longitude' => 112.7800,
            'is_active' => true,
        ]);

        $this->schoolA1 = School::where('school_code', 'SCH-SDN-01')->firstOrFail();
        $this->schoolA2 = School::where('school_code', 'SCH-SMPN-02')->firstOrFail();

        $this->schoolB1 = School::where('school_code', 'SCH-B1')->first() ?? School::create([
            'sppg_id' => $this->sppgB->id,
            'school_code' => 'SCH-B1',
            'name' => 'SD Negeri Kenjeran 01',
            'education_level' => 'SD',
            'public_slug' => 'sdn-kenjeran-01',
            'address' => 'Jl. Kenjeran Indah No. 12',
            'latitude' => -7.2450,
            'longitude' => 112.7850,
            'registered_beneficiaries' => 200,
            'is_active' => true,
        ]);

        $this->kepalaSppgA = User::where('role', UserRole::KEPALA_SPPG)->where('sppg_id', $this->sppgA->id)->firstOrFail();
        $this->kepalaSppgB = User::factory()->create([
            'sppg_id' => $this->sppgB->id,
            'role' => UserRole::KEPALA_SPPG,
            'name' => 'Kepala SPPG B',
            'email' => 'kepala.b@mbgtrack.test',
        ]);

        $this->kurirA = User::where('role', UserRole::KURIR)->where('sppg_id', $this->sppgA->id)->firstOrFail();
        $this->kurirB = User::factory()->create([
            'sppg_id' => $this->sppgB->id,
            'role' => UserRole::KURIR,
            'name' => 'Kurir Budi SPPG B',
            'email' => 'kurir.b@mbgtrack.test',
        ]);

        $this->asistenA = User::where('role', UserRole::ASISTEN_LAPANGAN)->where('sppg_id', $this->sppgA->id)->firstOrFail();

        $this->picSchoolA1 = User::where('role', UserRole::PIC_SEKOLAH)->where('school_id', $this->schoolA1->id)->firstOrFail();
        $this->picSchoolA2 = User::where('role', UserRole::PIC_SEKOLAH)->where('school_id', $this->schoolA2->id)->firstOrFail();
        $this->picSchoolB1 = User::factory()->create([
            'role' => UserRole::PIC_SEKOLAH,
            'school_id' => $this->schoolB1->id,
            'name' => 'PIC Sekolah B1',
            'email' => 'pic.b1@mbgtrack.test',
        ]);

        $this->vehicleA = Vehicle::where('sppg_id', $this->sppgA->id)->firstOrFail();
        $this->vehicleB = Vehicle::where('plate_number', 'L 9999 XX')->first() ?? Vehicle::create([
            'sppg_id' => $this->sppgB->id,
            'plate_number' => 'L 9999 XX',
            'vehicle_name' => 'Grand Max SPPG B',
            'vehicle_type' => 'BLINDVAN',
            'is_active' => true,
        ]);

        $this->menu = Menu::where('status', MenuStatus::ACTIVE)->first() ?? Menu::firstOrFail();

        $this->planA = ProductionPlan::create([
            'sppg_id' => $this->sppgA->id,
            'menu_id' => $this->menu->id,
            'service_date' => Carbon::today(),
            'target_portions' => 300,
            'status' => ProductionPlanStatus::COMPLETED,
            'created_by' => $this->kepalaSppgA->id,
        ]);

        $this->batchA = FoodBatch::create([
            'production_plan_id' => $this->planA->id,
            'menu_id' => $this->menu->id,
            'batch_code' => 'BATCH-EXITGATE-' . uniqid(),
            'portion_category' => 'REGULER',
            'planned_portions' => 300,
            'actual_portions' => 300,
            'status' => FoodBatchStatus::PACKED,
            'created_by' => $this->kepalaSppgA->id,
        ]);
    }

    /**
     * Helper to create a delivery context with a SchoolServiceRecord.
     */
    protected function createDeliveredReceipt(School $school, int $portions = 300): array
    {
        $trip = Trip::create([
            'trip_code' => 'TRIP-DELIV-' . uniqid(),
            'sppg_id' => $school->sppg_id,
            'driver_id' => $this->kurirA->id,
            'vehicle_id' => $this->vehicleA->id,
            'service_date' => Carbon::today(),
            'trip_type' => TripType::DELIVERY,
            'status' => TripStatus::IN_PROGRESS,
            'created_by' => $this->kepalaSppgA->id,
            'departure_time' => Carbon::now()->subHours(2),
        ]);

        $stop = TripStop::create([
            'trip_id' => $trip->id,
            'school_id' => $school->id,
            'sequence_number' => 1,
            'planned_portions' => $portions,
            'loaded_portions' => $portions,
            'status' => TripStopStatus::HANDED_OVER,
            'arrived_at' => Carbon::now()->subHour(),
            'departed_at' => Carbon::now()->subMinutes(30),
        ]);

        $serviceRecord = SchoolServiceRecord::create([
            'trip_stop_id' => $stop->id,
            'recipient_name' => 'Ibu Guru PIC',
            'recipient_role' => 'PIC Sekolah',
            'received_portions' => $portions,
            'expected_portions' => $portions,
            'damaged_portions' => 0,
            'shortage_portions' => 0,
            'status' => SchoolReceiptStatus::MEAL_COMPLETED,
            'received_at' => Carbon::now()->subMinutes(45),
            'meal_completed_at' => Carbon::now()->subMinutes(15),
        ]);

        return [$trip, $stop, $serviceRecord];
    }

    // =========================================================================
    // GATE A: EXPECTED TRAYS MUST HAVE VERIFIED PHYSICAL SOURCE
    // =========================================================================

    public function test_gate_a_300_portions_without_container_manifest_leaves_expected_unverified_and_null(): void
    {
        [$trip, $stop, $serviceRecord] = $this->createDeliveredReceipt($this->schoolA1, 300);

        $trayService = app(TrayReturnService::class);
        $trayReturn = $trayService->initiateForRecord($serviceRecord, null, false);

        $this->assertNull($trayReturn->expected_trays);
        $this->assertFalse($trayReturn->is_expected_verified);
        $this->assertNull($trayReturn->manifest_reference);
        $this->assertEquals(TrayReturnStatus::IN_USE, $trayReturn->status);

        // PIC marks ready with 25 actual physical thermal containers
        $trayReturn = $trayService->markReady($trayReturn, $this->picSchoolA1, [
            'ready_count' => 25,
            'damaged_count' => 0,
            'notes' => '25 boks thermal dicuci bersih dan siap',
        ]);

        // Must NOT trigger a false missing incident of 275 containers
        $this->assertEquals(TrayReturnStatus::READY_FOR_PICKUP, $trayReturn->status);
        $this->assertEquals(25, $trayReturn->ready_count);
        $this->assertEquals(0, Incident::where('subject_type', TrayReturn::class)->where('subject_id', $trayReturn->id)->count());
    }

    public function test_gate_a_zero_verified_containers_retains_zero_not_coerced_to_one(): void
    {
        [$trip, $stop, $serviceRecord] = $this->createDeliveredReceipt($this->schoolA1, 100);

        $trayService = app(TrayReturnService::class);
        // Disposable packaging delivery: 0 physical containers to return
        $trayReturn = $trayService->initiateForRecord($serviceRecord, 0, true, 'MANIFEST-DISPOSABLE-ZERO');

        $this->assertSame(0, $trayReturn->expected_trays);
        $this->assertTrue($trayReturn->is_expected_verified);
        $this->assertEquals('MANIFEST-DISPOSABLE-ZERO', $trayReturn->manifest_reference);
    }

    public function test_gate_a_verified_manifest_with_different_container_count_from_food_portions(): void
    {
        [$trip, $stop, $serviceRecord] = $this->createDeliveredReceipt($this->schoolA1, 300);

        $trayService = app(TrayReturnService::class);
        // Official manifest states exactly 20 stainless carrier boxes for the 300 portions
        $trayReturn = $trayService->initiateForRecord($serviceRecord, 20, true, 'MANIFEST-BOX-20');

        $this->assertSame(20, $trayReturn->expected_trays);
        $this->assertTrue($trayReturn->is_expected_verified);
        $this->assertEquals('MANIFEST-BOX-20', $trayReturn->manifest_reference);

        // PIC reports all 20 ready -> no discrepancy
        $trayReturn = $trayService->markReady($trayReturn, $this->picSchoolA1, [
            'ready_count' => 20,
            'damaged_count' => 0,
            'notes' => 'Semua 20 wadah siap',
        ]);
        $this->assertEquals(TrayReturnStatus::READY_FOR_PICKUP, $trayReturn->status);
        $this->assertEquals(0, Incident::where('subject_type', TrayReturn::class)->where('subject_id', $trayReturn->id)->count());

        // But if PIC reports 18 ready with 2 missing from verified 20 -> incident IS created against authentic baseline
        [$trip2, $stop2, $serviceRecord2] = $this->createDeliveredReceipt($this->schoolA1, 300);
        $trayReturn2 = $trayService->initiateForRecord($serviceRecord2, 20, true, 'MANIFEST-BOX-20-B');
        $trayReturn2 = $trayService->markReady($trayReturn2, $this->picSchoolA1, [
            'ready_count' => 18,
            'damaged_count' => 0,
            'notes' => '2 boks tertinggal di ruang kelas',
        ]);
        $this->assertEquals(1, Incident::where('subject_type', TrayReturn::class)->where('subject_id', $trayReturn2->id)->count());
    }

    public function test_gate_a_legacy_fallback_record_does_not_treat_fake_baseline_as_physical_incident(): void
    {
        [$trip, $stop, $serviceRecord] = $this->createDeliveredReceipt($this->schoolA1, 50);

        // Simulate historical unverified record
        $trayReturn = TrayReturn::create([
            'sppg_id' => $this->sppgA->id,
            'school_id' => $this->schoolA1->id,
            'school_service_record_id' => $serviceRecord->id,
            'status' => TrayReturnStatus::IN_USE,
            'expected_trays' => 50,
            'is_expected_verified' => false, // unverified historical fallback
        ]);

        $trayService = app(TrayReturnService::class);
        $trayReturn = $trayService->markReady($trayReturn, $this->picSchoolA1, [
            'ready_count' => 10,
            'damaged_count' => 0,
            'notes' => 'Fisik wadah dari pengiriman kemarin hanya 10 boks',
        ]);

        // Does not create false missing incident
        $this->assertEquals(0, Incident::where('subject_type', TrayReturn::class)->where('subject_id', $trayReturn->id)->count());
    }

    // =========================================================================
    // GATE B: OFFICIAL PICKUP ASSIGNMENT MUST BE ENFORCED
    // =========================================================================

    public function test_gate_b_courier_cannot_pickup_unassigned_ready_trays(): void
    {
        [$trip, $stop, $serviceRecord] = $this->createDeliveredReceipt($this->schoolA1, 40);
        $trayService = app(TrayReturnService::class);
        $trayReturn = $trayService->initiateForRecord($serviceRecord, 10, true, 'MAN-10');
        $trayReturn = $trayService->markReady($trayReturn, $this->picSchoolA1, [
            'ready_count' => 10,
            'damaged_count' => 0,
        ]);

        $this->assertEquals(TrayReturnStatus::READY_FOR_PICKUP, $trayReturn->status);
        $this->assertNull($trayReturn->pickup_trip_stop_id);

        // Courier attempts pickup without assignment
        $response = $this->actingAs($this->kurirA)->post(route('driver.tray-returns.pickup', $trayReturn), [
            'picked_up_count' => 10,
        ]);
        $response->assertForbidden();
    }

    public function test_gate_b_kepala_sppg_officially_assigns_pickup_and_courier_succeeds(): void
    {
        [$trip, $stop, $serviceRecord] = $this->createDeliveredReceipt($this->schoolA1, 40);
        $trayService = app(TrayReturnService::class);
        $trayReturn = $trayService->initiateForRecord($serviceRecord, 10, true, 'MAN-10');
        $trayReturn = $trayService->markReady($trayReturn, $this->picSchoolA1, [
            'ready_count' => 10,
            'damaged_count' => 0,
        ]);

        // Create an active pickup trip with a valid stop at School A1 assigned to Kurir A
        $pickupTrip = Trip::create([
            'trip_code' => 'TRIP-PKUP-' . uniqid(),
            'sppg_id' => $this->sppgA->id,
            'driver_id' => $this->kurirA->id,
            'vehicle_id' => $this->vehicleA->id,
            'service_date' => Carbon::today(),
            'trip_type' => TripType::PICKUP,
            'status' => TripStatus::READY,
            'created_by' => $this->kepalaSppgA->id,
        ]);

        $pickupStop = TripStop::create([
            'trip_id' => $pickupTrip->id,
            'school_id' => $this->schoolA1->id,
            'sequence_number' => 1,
            'planned_portions' => 0,
            'loaded_portions' => 0,
            'status' => TripStopStatus::PENDING,
        ]);

        // Kepala SPPG officially assigns via POST endpoint
        $assignResponse = $this->actingAs($this->kepalaSppgA)->post(route('head.distribution.trays.assign', $trayReturn), [
            'pickup_trip_stop_id' => $pickupStop->id,
        ]);
        $assignResponse->assertRedirect();

        $trayReturn->refresh();
        $this->assertEquals(TrayReturnStatus::PICKUP_ASSIGNED, $trayReturn->status);
        $this->assertEquals($pickupStop->id, $trayReturn->pickup_trip_stop_id);

        // Assigned Kurir A picks up successfully
        $pickupResponse = $this->actingAs($this->kurirA)->post(route('driver.tray-returns.pickup', $trayReturn), [
            'picked_up_count' => 10,
            'notes' => '10 boks diangkut',
        ]);
        $pickupResponse->assertRedirect(route('driver.pickups.index'));

        $trayReturn->refresh();
        $this->assertEquals(TrayReturnStatus::PICKED_UP, $trayReturn->status);
        $this->assertEquals(10, $trayReturn->picked_up_count);
    }

    public function test_gate_b_other_courier_cannot_pickup_task_assigned_to_different_driver(): void
    {
        [$trip, $stop, $serviceRecord] = $this->createDeliveredReceipt($this->schoolA1, 40);
        $trayService = app(TrayReturnService::class);
        $trayReturn = $trayService->initiateForRecord($serviceRecord, 10, true, 'MAN-10');
        $trayReturn = $trayService->markReady($trayReturn, $this->picSchoolA1, ['ready_count' => 10]);

        $pickupTrip = Trip::create([
            'trip_code' => 'TRIP-PKUP-' . uniqid(),
            'sppg_id' => $this->sppgA->id,
            'driver_id' => $this->kurirA->id,
            'vehicle_id' => $this->vehicleA->id,
            'service_date' => Carbon::today(),
            'trip_type' => TripType::PICKUP,
            'status' => TripStatus::READY,
            'created_by' => $this->kepalaSppgA->id,
        ]);

        $pickupStop = TripStop::create([
            'trip_id' => $pickupTrip->id,
            'school_id' => $this->schoolA1->id,
            'sequence_number' => 1,
            'status' => TripStopStatus::PENDING,
        ]);

        $trayService->assignPickup($trayReturn, $this->kepalaSppgA, $pickupStop);

        // Another courier from the same SPPG
        $kurirA2 = User::factory()->create([
            'sppg_id' => $this->sppgA->id,
            'role' => UserRole::KURIR,
            'name' => 'Kurir A2',
            'email' => 'kurir.a2@mbgtrack.test',
        ]);

        $response = $this->actingAs($kurirA2)->post(route('driver.tray-returns.pickup', $trayReturn), [
            'picked_up_count' => 10,
        ]);
        $response->assertForbidden();
    }

    public function test_gate_b_invalid_assignment_scenarios_are_strictly_rejected(): void
    {
        [$trip, $stop, $serviceRecord] = $this->createDeliveredReceipt($this->schoolA1, 40);
        $trayService = app(TrayReturnService::class);
        $trayReturn = $trayService->initiateForRecord($serviceRecord, 10, true, 'MAN-10');
        $trayReturn = $trayService->markReady($trayReturn, $this->picSchoolA1, ['ready_count' => 10]);

        // 1. Cross-tenant Kepala SPPG B cannot assign tray of SPPG A
        $response1 = $this->actingAs($this->kepalaSppgB)->post(route('head.distribution.trays.assign', $trayReturn), [
            'pickup_trip_stop_id' => $stop->id,
        ]);
        $response1->assertForbidden();

        // 2. Stop belonging to a different school is rejected with 422
        $tripOtherSchool = Trip::create([
            'trip_code' => 'TRIP-DIFF-SCH-' . uniqid(),
            'sppg_id' => $this->sppgA->id,
            'driver_id' => $this->kurirA->id,
            'vehicle_id' => $this->vehicleA->id,
            'service_date' => Carbon::today(),
            'trip_type' => TripType::PICKUP,
            'status' => TripStatus::READY,
            'created_by' => $this->kepalaSppgA->id,
        ]);
        $stopOtherSchool = TripStop::create([
            'trip_id' => $tripOtherSchool->id,
            'school_id' => $this->schoolA2->id, // School A2, while tray is School A1
            'sequence_number' => 1,
            'status' => TripStopStatus::PENDING,
        ]);

        $response2 = $this->actingAs($this->kepalaSppgA)->post(route('head.distribution.trays.assign', $trayReturn), [
            'pickup_trip_stop_id' => $stopOtherSchool->id,
        ]);
        $response2->assertSessionHasErrors(['stop']);

        // 3. Stop on a COMPLETED trip is rejected
        $tripCompleted = Trip::create([
            'trip_code' => 'TRIP-COMPL-' . uniqid(),
            'sppg_id' => $this->sppgA->id,
            'driver_id' => $this->kurirA->id,
            'vehicle_id' => $this->vehicleA->id,
            'service_date' => Carbon::today(),
            'trip_type' => TripType::PICKUP,
            'status' => TripStatus::COMPLETED,
            'created_by' => $this->kepalaSppgA->id,
        ]);
        $stopOnCompletedTrip = TripStop::create([
            'trip_id' => $tripCompleted->id,
            'school_id' => $this->schoolA1->id,
            'sequence_number' => 1,
            'status' => TripStopStatus::PENDING,
        ]);

        $response3 = $this->actingAs($this->kepalaSppgA)->post(route('head.distribution.trays.assign', $trayReturn), [
            'pickup_trip_stop_id' => $stopOnCompletedTrip->id,
        ]);
        $response3->assertSessionHasErrors(['trip']);
    }

    public function test_gate_b_two_couriers_concurrency_protection(): void
    {
        [$trip, $stop, $serviceRecord] = $this->createDeliveredReceipt($this->schoolA1, 40);
        $trayService = app(TrayReturnService::class);
        $trayReturn = $trayService->initiateForRecord($serviceRecord, 10, true, 'MAN-10');
        $trayReturn = $trayService->markReady($trayReturn, $this->picSchoolA1, ['ready_count' => 10]);

        $pickupTrip = Trip::create([
            'trip_code' => 'TRIP-PKUP-' . uniqid(),
            'sppg_id' => $this->sppgA->id,
            'driver_id' => $this->kurirA->id,
            'vehicle_id' => $this->vehicleA->id,
            'service_date' => Carbon::today(),
            'trip_type' => TripType::PICKUP,
            'status' => TripStatus::READY,
            'created_by' => $this->kepalaSppgA->id,
        ]);

        $pickupStop = TripStop::create([
            'trip_id' => $pickupTrip->id,
            'school_id' => $this->schoolA1->id,
            'sequence_number' => 1,
            'status' => TripStopStatus::PENDING,
        ]);

        $trayService->assignPickup($trayReturn, $this->kepalaSppgA, $pickupStop);

        // Courier A performs pickup
        $resA = $this->actingAs($this->kurirA)->post(route('driver.tray-returns.pickup', $trayReturn), [
            'picked_up_count' => 10,
            'notes' => 'Kurir A mengambil pertama',
        ]);
        $resA->assertRedirect();

        // Courier B (unauthorized or concurrent) tries to pick up the same record
        $kurirB2 = User::factory()->create([
            'sppg_id' => $this->sppgA->id,
            'role' => UserRole::KURIR,
        ]);
        $resB = $this->actingAs($kurirB2)->post(route('driver.tray-returns.pickup', $trayReturn), [
            'picked_up_count' => 10,
        ]);
        $resB->assertForbidden();

        // Repeated request by Kurir A returns idempotently without second activity log
        $activityCountBefore = ActivityLog::where('auditable_type', TrayReturn::class)->where('auditable_id', $trayReturn->id)->count();
        $resA2 = $this->actingAs($this->kurirA)->post(route('driver.tray-returns.pickup', $trayReturn), [
            'picked_up_count' => 10,
            'notes' => 'Kurir A mengambil pertama',
        ]);
        $resA2->assertRedirect();
        $activityCountAfter = ActivityLog::where('auditable_type', TrayReturn::class)->where('auditable_id', $trayReturn->id)->count();
        $this->assertEquals($activityCountBefore, $activityCountAfter);
    }

    // =========================================================================
    // GATE C: CROSS-TENANT INCIDENT CREATION SECURITY
    // =========================================================================

    public function test_gate_c_reporter_sppg_spoofing_is_ignored_and_derived_from_actor(): void
    {
        $response = $this->actingAs($this->kurirA)->post(route('incidents.store'), [
            'sppg_id' => $this->sppgB->id, // Attempt to spoof SPPG B
            'category' => IncidentCategory::VEHICLE_BREAKDOWN->value,
            'severity' => IncidentSeverity::HIGH->value,
            'description' => 'Ban armada kempes di jalan protokol.',
        ]);

        $response->assertRedirect();

        $incident = Incident::latest('id')->firstOrFail();
        // Disregarded client input; securely anchored to Kurir A's SPPG A
        $this->assertEquals($this->sppgA->id, $incident->sppg_id);
    }

    public function test_gate_c_pic_sekolah_cannot_report_for_different_school(): void
    {
        // PIC of School A1 attempts to create incident for School A2
        $response = $this->actingAs($this->picSchoolA1)->post(route('incidents.store'), [
            'school_id' => $this->schoolA2->id,
            'category' => IncidentCategory::FOOD_SAFETY->value,
            'severity' => IncidentSeverity::CRITICAL->value,
            'description' => 'Mencoba membuat laporan untuk sekolah lain.',
        ]);

        $response->assertSessionHasErrors(['school_id']);
    }

    public function test_gate_c_internal_user_cannot_target_cross_tenant_school(): void
    {
        // Kurir of SPPG A attempts to report incident with School B1 (belongs to SPPG B)
        $response = $this->actingAs($this->kurirA)->post(route('incidents.store'), [
            'school_id' => $this->schoolB1->id,
            'category' => IncidentCategory::OTHER->value,
            'severity' => IncidentSeverity::MEDIUM->value,
            'description' => 'Insiden pada sekolah tenant asing.',
        ]);

        $response->assertForbidden();
    }

    public function test_gate_c_arbitrary_subject_class_is_rejected(): void
    {
        $response = $this->actingAs($this->kurirA)->post(route('incidents.store'), [
            'category' => IncidentCategory::OTHER->value,
            'severity' => IncidentSeverity::LOW->value,
            'description' => 'Percobaan subject class arbitrer.',
            'subject_type' => User::class,
            'subject_id' => $this->kurirA->id,
        ]);

        $response->assertSessionHasErrors(['subject_type']);
    }

    public function test_gate_c_non_existent_subject_id_is_rejected(): void
    {
        $response = $this->actingAs($this->kurirA)->post(route('incidents.store'), [
            'category' => IncidentCategory::OTHER->value,
            'severity' => IncidentSeverity::LOW->value,
            'description' => 'Subjek tidak ada di database.',
            'subject_type' => 'trip',
            'subject_id' => 99999999,
        ]);

        $response->assertSessionHasErrors(['subject_id']);
    }

    public function test_gate_c_cross_tenant_subject_is_rejected_with_403(): void
    {
        // Create trip for SPPG B
        $tripB = Trip::create([
            'trip_code' => 'TRIP-B-' . uniqid(),
            'sppg_id' => $this->sppgB->id,
            'driver_id' => $this->kurirB->id,
            'vehicle_id' => $this->vehicleB->id,
            'service_date' => Carbon::today(),
            'trip_type' => TripType::DELIVERY,
            'status' => TripStatus::READY,
            'created_by' => $this->kepalaSppgB->id,
        ]);

        // Kurir A from SPPG A targets Trip B
        $response = $this->actingAs($this->kurirA)->post(route('incidents.store'), [
            'category' => IncidentCategory::VEHICLE_BREAKDOWN->value,
            'severity' => IncidentSeverity::HIGH->value,
            'description' => 'Menyerang trip tenant B.',
            'subject_type' => 'trip',
            'subject_id' => $tripB->id,
        ]);

        $response->assertForbidden();
    }

    public function test_gate_c_unauthorized_user_cannot_view_cross_tenant_incident(): void
    {
        $incidentA = Incident::create([
            'sppg_id' => $this->sppgA->id,
            'school_id' => $this->schoolA1->id,
            'subject_type' => School::class,
            'subject_id' => $this->schoolA1->id,
            'reported_by' => $this->picSchoolA1->id,
            'category' => IncidentCategory::PORTION_SHORTAGE,
            'severity' => IncidentSeverity::MEDIUM,
            'status' => IncidentStatus::OPEN,
            'description' => 'Kekurangan porsi rahasia internal SPPG A.',
        ]);

        // Staff from SPPG B attempts to view incident A
        $response = $this->actingAs($this->kepalaSppgB)->get(route('incidents.show', $incidentA));
        $response->assertForbidden();
    }

    // =========================================================================
    // GATE D: PICKUP DISCREPANCY MUST REMAIN IN DEPOT QUEUE
    // =========================================================================

    public function test_gate_d_pickup_discrepancy_remains_in_a03_incoming_queue_until_depot_reception(): void
    {
        [$trip, $stop, $serviceRecord] = $this->createDeliveredReceipt($this->schoolA1, 50);
        $trayService = app(TrayReturnService::class);
        $trayReturn = $trayService->initiateForRecord($serviceRecord, 50, true, 'MAN-50');

        // School reports 50 ready
        $trayReturn = $trayService->markReady($trayReturn, $this->picSchoolA1, [
            'ready_count' => 50,
            'damaged_count' => 0,
        ]);

        // Assign to Kurir A
        $trayService->assignPickup($trayReturn, $this->kepalaSppgA, $stop);

        // Kurir records pickup with discrepancy: picked_up_count = 45 (5 missing at school)
        $trayReturn = $trayService->recordPickup($trayReturn, $this->kurirA, [
            'picked_up_count' => 45,
            'notes' => '5 wadah tidak ditemukan saat penjemputan oleh kurir',
        ]);

        $this->assertEquals(TrayReturnStatus::DISCREPANCY, $trayReturn->status);
        $this->assertNotNull($trayReturn->picked_up_at);
        $this->assertNull($trayReturn->returned_at);

        // Asisten Lapangan opens Page A03
        $a03Response = $this->actingAs($this->asistenA)->get(route('production.logistics.index'));
        $a03Response->assertOk();

        // Must be in incomingReturns (in transit to depot)
        $incomingReturns = $a03Response->viewData('incomingReturns');
        $this->assertTrue($incomingReturns->contains('id', $trayReturn->id), 'Discrepant pickup MUST appear in incomingReturns queue on Page A03.');

        // Must NOT be in completedReturns ledger
        $completedReturns = $a03Response->viewData('completedReturns');
        $this->assertFalse($completedReturns->contains('id', $trayReturn->id), 'Discrepant pickup with returned_at=NULL must NOT appear in completed returns ledger.');

        // Now physical depot reception occurs (Asisten receives 45 returned at SPPG depot)
        $receiveResponse = $this->actingAs($this->asistenA)->post(route('production.tray-returns.receive', $trayReturn), [
            'returned_count' => 45,
            'damaged_count' => 0,
            'missing_count' => 0,
            'notes' => '45 wadah fisik diterima lengkap sesuai muatan kurir',
        ]);
        $receiveResponse->assertRedirect(route('production.logistics.index'));

        $trayReturn->refresh();
        $this->assertNotNull($trayReturn->returned_at);

        // On subsequent visit to A03, it has moved to completedReturns
        $a03AfterReceive = $this->actingAs($this->asistenA)->get(route('production.logistics.index'));
        $incomingAfter = $a03AfterReceive->viewData('incomingReturns');
        $completedAfter = $a03AfterReceive->viewData('completedReturns');

        $this->assertFalse($incomingAfter->contains('id', $trayReturn->id));
        $this->assertTrue($completedAfter->contains('id', $trayReturn->id));

        // Original courier discrepancy incident remains OPEN and traceable
        $this->assertDatabaseHas('incidents', [
            'subject_type' => TrayReturn::class,
            'subject_id' => $trayReturn->id,
            'status' => IncidentStatus::OPEN->value,
            'reported_by' => $this->kurirA->id,
        ]);
    }

    // =========================================================================
    // GATE E: IDEMPOTENCY AND INCIDENT DEDUPLICATION
    // =========================================================================

    public function test_gate_e_pickup_retry_is_idempotent_without_duplicate_incidents_or_timestamp_bump(): void
    {
        [$trip, $stop, $serviceRecord] = $this->createDeliveredReceipt($this->schoolA1, 50);
        $trayService = app(TrayReturnService::class);
        $trayReturn = $trayService->initiateForRecord($serviceRecord, 50, true, 'MAN-50');
        $trayReturn = $trayService->markReady($trayReturn, $this->picSchoolA1, ['ready_count' => 50]);
        $trayService->assignPickup($trayReturn, $this->kepalaSppgA, $stop);

        // First attempt with discrepancy
        $trayReturn = $trayService->recordPickup($trayReturn, $this->kurirA, [
            'picked_up_count' => 45,
            'notes' => '5 wadah kurang',
        ]);
        $originalPickedUpAt = $trayReturn->picked_up_at->toIso8601String();
        $initialIncidentCount = Incident::where('subject_type', TrayReturn::class)->where('subject_id', $trayReturn->id)->count();
        $initialActivityCount = ActivityLog::where('auditable_type', TrayReturn::class)->where('auditable_id', $trayReturn->id)->count();

        $this->assertEquals(1, $initialIncidentCount);

        // Retry identically 3 times
        for ($i = 0; $i < 3; $i++) {
            $retryReturn = $trayService->recordPickup($trayReturn, $this->kurirA, [
                'picked_up_count' => 45,
                'notes' => '5 wadah kurang',
            ]);
            $this->assertEquals($originalPickedUpAt, $retryReturn->picked_up_at->toIso8601String());
        }

        // Must still have exactly 1 incident and no duplicate activity logs
        $finalIncidentCount = Incident::where('subject_type', TrayReturn::class)->where('subject_id', $trayReturn->id)->count();
        $finalActivityCount = ActivityLog::where('auditable_type', TrayReturn::class)->where('auditable_id', $trayReturn->id)->count();

        $this->assertEquals(1, $finalIncidentCount, 'Identical pickup retry must NOT spawn duplicate incidents.');
        $this->assertEquals($initialActivityCount, $finalActivityCount, 'Identical pickup retry must NOT create duplicate activity logs.');
    }

    public function test_gate_e_depot_reception_retry_is_idempotent(): void
    {
        [$trip, $stop, $serviceRecord] = $this->createDeliveredReceipt($this->schoolA1, 50);
        $trayService = app(TrayReturnService::class);
        $trayReturn = $trayService->initiateForRecord($serviceRecord, 50, true, 'MAN-50');
        $trayReturn = $trayService->markReady($trayReturn, $this->picSchoolA1, ['ready_count' => 50]);
        $trayService->assignPickup($trayReturn, $this->kepalaSppgA, $stop);
        $trayReturn = $trayService->recordPickup($trayReturn, $this->kurirA, ['picked_up_count' => 50]);

        // Depot receives with discrepancy (45 returned, 3 damaged, 2 missing)
        $trayReturn = $trayService->receiveAtDepot($trayReturn, $this->asistenA, [
            'returned_count' => 45,
            'damaged_count' => 3,
            'missing_count' => 2,
            'notes' => 'Kerusakan fisik saat perjalanan',
        ]);

        $originalReturnedAt = $trayReturn->returned_at->toIso8601String();
        $depotIncidentCount = Incident::where('subject_type', TrayReturn::class)
            ->where('subject_id', $trayReturn->id)
            ->where('reported_by', $this->asistenA->id)
            ->count();
        $this->assertEquals(1, $depotIncidentCount);

        // Retry 3 times identically
        for ($i = 0; $i < 3; $i++) {
            $retry = $trayService->receiveAtDepot($trayReturn, $this->asistenA, [
                'returned_count' => 45,
                'damaged_count' => 3,
                'missing_count' => 2,
                'notes' => 'Kerusakan fisik saat perjalanan',
            ]);
            $this->assertEquals($originalReturnedAt, $retry->returned_at->toIso8601String());
        }

        $finalDepotIncidentCount = Incident::where('subject_type', TrayReturn::class)
            ->where('subject_id', $trayReturn->id)
            ->where('reported_by', $this->asistenA->id)
            ->count();
        $this->assertEquals(1, $finalDepotIncidentCount);
    }

    // =========================================================================
    // GATE F: READ-ONLY ENDPOINT MUST NOT MUTATE BUSINESS STATE
    // =========================================================================

    public function test_gate_f_school_tray_index_does_not_mutate_business_state(): void
    {
        [$trip, $stop, $serviceRecord] = $this->createDeliveredReceipt($this->schoolA1, 200);

        // Assert no TrayReturn exists yet
        $this->assertEquals(0, TrayReturn::where('school_service_record_id', $serviceRecord->id)->count());
        $initialActivityLogCount = ActivityLog::count();

        // PIC School visits GET /school/trays
        $response1 = $this->actingAs($this->picSchoolA1)->get(route('school.trays.index'));
        $response1->assertOk();

        // Database MUST NOT be mutated by GET request
        $this->assertEquals(0, TrayReturn::where('school_service_record_id', $serviceRecord->id)->count());
        $this->assertEquals($initialActivityLogCount, ActivityLog::count());

        // Repeated visit produces 0 new records
        $response2 = $this->actingAs($this->picSchoolA1)->get(route('school.trays.index'));
        $response2->assertOk();
        $this->assertEquals(0, TrayReturn::where('school_service_record_id', $serviceRecord->id)->count());
        $this->assertEquals($initialActivityLogCount, ActivityLog::count());
    }

    public function test_gate_f_backfill_artisan_command_safely_initializes_uninitiated_records(): void
    {
        [$trip, $stop, $serviceRecord] = $this->createDeliveredReceipt($this->schoolA1, 200);

        $this->assertEquals(0, TrayReturn::where('school_service_record_id', $serviceRecord->id)->count());

        // Run artisan command
        Artisan::call('trays:backfill', ['--school_id' => $this->schoolA1->id]);

        $this->assertEquals(1, TrayReturn::where('school_service_record_id', $serviceRecord->id)->count());
        $backfilled = TrayReturn::where('school_service_record_id', $serviceRecord->id)->first();

        // Must be unverified and null expected_trays
        $this->assertNull($backfilled->expected_trays);
        $this->assertFalse($backfilled->is_expected_verified);
    }
}
