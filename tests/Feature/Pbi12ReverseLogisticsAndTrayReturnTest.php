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
use App\Models\EvidencePhoto;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

class Pbi12ReverseLogisticsAndTrayReturnTest extends TestCase
{
    use RefreshDatabase;

    protected Sppg $sppg;
    protected Sppg $otherSppg;
    protected School $schoolA;
    protected School $schoolB;
    protected User $driver;
    protected User $otherDriver;
    protected User $asisten;
    protected User $kepalaSppg;
    protected User $otherKepalaSppg;
    protected User $picSchoolA;
    protected User $picSchoolB;
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
            'sppg_id' => $this->otherSppg->id,
            'role' => UserRole::KURIR,
            'name' => 'Budi Driver Lain',
            'email' => 'other.driver@mbgtrack.test',
        ]);

        $this->asisten = User::where('role', UserRole::ASISTEN_LAPANGAN)
            ->where('sppg_id', $this->sppg->id)
            ->firstOrFail();

        $this->kepalaSppg = User::where('role', UserRole::KEPALA_SPPG)
            ->where('sppg_id', $this->sppg->id)
            ->firstOrFail();

        $this->otherKepalaSppg = User::factory()->create([
            'sppg_id' => $this->otherSppg->id,
            'role' => UserRole::KEPALA_SPPG,
            'name' => 'Kepala SPPG Luar',
            'email' => 'other.kepala@mbgtrack.test',
        ]);

        $this->picSchoolA = User::where('role', UserRole::PIC_SEKOLAH)
            ->where('school_id', $this->schoolA->id)
            ->firstOrFail();

        $this->picSchoolB = User::where('role', UserRole::PIC_SEKOLAH)
            ->where('school_id', $this->schoolB->id)
            ->firstOrFail();

        $this->vehicle = Vehicle::where('sppg_id', $this->sppg->id)->firstOrFail();

        $this->menu = Menu::where('status', MenuStatus::ACTIVE)->first() ?? Menu::firstOrFail();
        $this->plan = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->menu->id,
            'service_date' => Carbon::today(),
            'target_portions' => 200,
            'status' => \App\Enums\ProductionPlanStatus::COMPLETED,
            'created_by' => $this->kepalaSppg->id,
        ]);
        $this->batch = FoodBatch::create([
            'production_plan_id' => $this->plan->id,
            'menu_id' => $this->menu->id,
            'batch_code' => 'BATCH-PBI12-' . uniqid(),
            'portion_category' => 'REGULER',
            'planned_portions' => 200,
            'actual_portions' => 200,
            'status' => FoodBatchStatus::PACKED,
            'created_by' => $this->kepalaSppg->id,
        ]);
    }

    /**
     * Helper to create a delivered trip stop with a SchoolServiceRecord and TrayReturn.
     */
    protected function createDeliveredStopWithTray(School $school, int $portions = 50): array
    {
        $trip = Trip::create([
            'trip_code' => 'TRIP-RL-' . uniqid(),
            'sppg_id' => $this->sppg->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'service_date' => Carbon::today(),
            'trip_type' => TripType::DELIVERY,
            'status' => TripStatus::IN_PROGRESS,
            'created_by' => $this->kepalaSppg->id,
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

        $portionReq = \App\Models\DailyPortionRequest::firstOrCreate(
            ['school_id' => $school->id, 'service_date' => Carbon::today()],
            [
                'sppg_id' => $this->sppg->id,
                'registered_count' => $portions,
                'requested_portions' => $portions,
                'status' => \App\Enums\PortionRequestStatus::CONFIRMED,
                'created_by' => $this->picSchoolA->id,
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
                'allocated_portions' => $portions,
            ]
        );

        DeliveryItem::create([
            'trip_stop_id' => $stop->id,
            'food_batch_id' => $this->batch->id,
            'production_allocation_id' => $alloc->id,
            'planned_portions' => $portions,
            'loaded_portions' => $portions,
            'delivered_portions' => $portions,
            'school_id' => $school->id,
            'status' => 'DELIVERED',
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

        $trayReturn = TrayReturn::create([
            'school_service_record_id' => $serviceRecord->id,
            'status' => TrayReturnStatus::IN_USE,
            'expected_trays' => $portions,
        ]);

        return [$trip, $stop, $serviceRecord, $trayReturn];
    }

    /**
     * GATE 4: PIC Sekolah isolation and mark ready workflow.
     */
    public function test_pic_sekolah_can_view_and_mark_trays_ready_for_own_school(): void
    {
        [$trip, $stop, $serviceRecord, $tray] = $this->createDeliveredStopWithTray($this->schoolA, 60);

        // PIC A can view Page P06
        $response = $this->actingAs($this->picSchoolA)->get(route('school.trays.index'));
        $response->assertOk();
        $response->assertSee('Pelacakan & Kesiapan Ompreng', false);
        $response->assertSee('60');

        // PIC A can submit mark ready
        $photo = UploadedFile::fake()->image('ready-trays.jpg', 600, 600);
        $markResponse = $this->actingAs($this->picSchoolA)->post(route('school.trays.ready', $tray), [
            'ready_count' => 60,
            'damaged_count' => 0,
            'notes' => 'Wadah telah dicuci bersih dan dikelompokkan',
            'photo' => $photo,
        ]);

        $markResponse->assertRedirect(route('school.trays.index'));

        $tray->refresh();
        $this->assertEquals(TrayReturnStatus::READY_FOR_PICKUP, $tray->status);
        $this->assertEquals(60, $tray->ready_count);
        $this->assertNotNull($tray->ready_at);
        $this->assertEquals('Wadah telah dicuci bersih dan dikelompokkan', $tray->notes);
        $this->assertCount(1, $tray->evidencePhotos);

        // Audit log created
        $this->assertDatabaseHas('activity_logs', [
            'auditable_type' => TrayReturn::class,
            'auditable_id' => $tray->id,
            'event' => 'tray_return.marked_ready',
        ]);
    }

    /**
     * GATE 8: School isolation - PIC of School B cannot mark ready on School A trays.
     */
    public function test_pic_cannot_mark_trays_ready_for_different_school(): void
    {
        [$trip, $stop, $serviceRecord, $trayA] = $this->createDeliveredStopWithTray($this->schoolA, 40);

        $response = $this->actingAs($this->picSchoolB)->post(route('school.trays.ready', $trayA), [
            'ready_count' => 40,
        ]);

        $response->assertForbidden();
    }

    /**
     * GATE 2: Concurrency & invalid state transition - Cannot mark ready if already READY_FOR_PICKUP.
     */
    public function test_cannot_mark_ready_when_not_in_use_state(): void
    {
        [$trip, $stop, $serviceRecord, $tray] = $this->createDeliveredStopWithTray($this->schoolA, 30);

        $tray->update([
            'status' => TrayReturnStatus::READY_FOR_PICKUP,
            'ready_count' => 30,
            'ready_at' => Carbon::now(),
        ]);

        // Attempting to mark ready again with conflicting count should result in 409 conflict
        $response = $this->actingAs($this->picSchoolA)->postJson(route('school.trays.ready', $tray), [
            'ready_count' => 25,
        ]);

        $response->assertStatus(409);
    }

    /**
     * GATE 5: Driver pickup workflow & physical count integrity.
     */
    public function test_driver_can_pickup_ready_trays_with_physical_count(): void
    {
        [$trip, $stop, $serviceRecord, $tray] = $this->createDeliveredStopWithTray($this->schoolA, 50);

        $tray->update([
            'status' => TrayReturnStatus::PICKUP_ASSIGNED,
            'pickup_trip_stop_id' => $stop->id,
            'ready_count' => 50,
            'ready_at' => Carbon::now()->subMinutes(15),
        ]);

        // Driver views Page D05
        $indexResponse = $this->actingAs($this->driver)->get(route('driver.pickups.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Penjemputan Wadah & Ompreng', false);
        $indexResponse->assertSee($this->schoolA->name);

        // Driver records pickup
        $photo = UploadedFile::fake()->image('driver-pickup.jpg', 600, 600);
        $pickupResponse = $this->actingAs($this->driver)->post(route('driver.tray-returns.pickup', $tray), [
            'picked_up_count' => 50,
            'notes' => 'Fisik lengkap diangkut ke mobil',
            'photo' => $photo,
        ]);

        $pickupResponse->assertRedirect(route('driver.pickups.index'));

        $tray->refresh();
        $this->assertEquals(TrayReturnStatus::PICKED_UP, $tray->status);
        $this->assertEquals(50, $tray->picked_up_count);
        $this->assertNotNull($tray->picked_up_at);
        $this->assertCount(1, $tray->evidencePhotos);

        // Audit log created
        $this->assertDatabaseHas('activity_logs', [
            'auditable_type' => TrayReturn::class,
            'auditable_id' => $tray->id,
            'event' => 'tray_return.picked_up',
        ]);
    }

    /**
     * GATE 3: Discrepancy during courier pickup creates an incident and sets status to DISCREPANCY.
     */
    public function test_driver_pickup_discrepancy_creates_incident(): void
    {
        [$trip, $stop, $serviceRecord, $tray] = $this->createDeliveredStopWithTray($this->schoolA, 50);

        $tray->update([
            'status' => TrayReturnStatus::PICKUP_ASSIGNED,
            'pickup_trip_stop_id' => $stop->id,
            'ready_count' => 50,
            'ready_at' => Carbon::now()->subMinutes(20),
        ]);

        // Courier only receives 45 ompreng (5 missing at school)
        $response = $this->actingAs($this->driver)->post(route('driver.tray-returns.pickup', $tray), [
            'picked_up_count' => 45,
            'notes' => '5 ompreng tidak ditemukan saat penjemputan',
        ]);

        $response->assertRedirect(route('driver.pickups.index'));

        $tray->refresh();
        $this->assertEquals(TrayReturnStatus::DISCREPANCY, $tray->status);
        $this->assertEquals(45, $tray->picked_up_count);

        // Incident created
        $this->assertDatabaseHas('incidents', [
            'sppg_id' => $this->sppg->id,
            'school_id' => $this->schoolA->id,
            'category' => IncidentCategory::TRAY_DISCREPANCY->value,
            'status' => IncidentStatus::OPEN->value,
        ]);
    }

    /**
     * GATE 6: SPPG depot reception, reconciliation, and physical count provenance.
     */
    public function test_depot_staff_reconciles_returning_trays(): void
    {
        [$trip, $stop, $serviceRecord, $tray] = $this->createDeliveredStopWithTray($this->schoolA, 75);

        $tray->update([
            'status' => TrayReturnStatus::PICKED_UP,
            'ready_count' => 75,
            'picked_up_count' => 75,
            'ready_at' => Carbon::now()->subHours(2),
            'picked_up_at' => Carbon::now()->subHour(),
        ]);

        // Asisten views Page A03
        $indexResponse = $this->actingAs($this->asisten)->get(route('production.logistics.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Penerimaan & Rekonsiliasi Ompreng SPPG', false);
        $indexResponse->assertSee($this->schoolA->name);

        // Asisten records depot return (73 good, 2 damaged, 0 missing)
        $photo = UploadedFile::fake()->image('depot-receive.jpg', 600, 600);
        $receiveResponse = $this->actingAs($this->asisten)->post(route('production.tray-returns.receive', $tray), [
            'returned_count' => 73,
            'damaged_count' => 2,
            'missing_count' => 0,
            'notes' => '2 wadah retak saat diperiksa di depot',
            'photo' => $photo,
        ]);

        $receiveResponse->assertRedirect(route('production.logistics.index'));

        $tray->refresh();
        $this->assertEquals(TrayReturnStatus::RETURNED_TO_SPPG, $tray->status);
        $this->assertEquals(73, $tray->returned_count);
        $this->assertEquals(2, $tray->damaged_count);
        $this->assertEquals(0, $tray->missing_count);
        $this->assertNotNull($tray->returned_at);

        // Original courier count is NOT overwritten
        $this->assertEquals(75, $tray->picked_up_count);
    }

    /**
     * GATE 6 & 8: Depot reception discrepancy when counts do not reconcile.
     */
    public function test_depot_reception_discrepancy_creates_incident(): void
    {
        [$trip, $stop, $serviceRecord, $tray] = $this->createDeliveredStopWithTray($this->schoolA, 50);

        $tray->update([
            'status' => TrayReturnStatus::PICKED_UP,
            'ready_count' => 50,
            'picked_up_count' => 50,
            'ready_at' => Carbon::now()->subHours(2),
            'picked_up_at' => Carbon::now()->subHour(),
        ]);

        // Depot counts 47 returned, 0 damaged, 3 missing
        $response = $this->actingAs($this->asisten)->post(route('production.tray-returns.receive', $tray), [
            'returned_count' => 47,
            'damaged_count' => 0,
            'missing_count' => 3,
            'notes' => '3 wadah hilang dalam perjalanan balik',
        ]);

        $response->assertRedirect(route('production.logistics.index'));

        $tray->refresh();
        $this->assertEquals(TrayReturnStatus::DISCREPANCY, $tray->status);
        $this->assertEquals(47, $tray->returned_count);
        $this->assertEquals(3, $tray->missing_count);

        // Incident created
        $this->assertDatabaseHas('incidents', [
            'sppg_id' => $this->sppg->id,
            'school_id' => $this->schoolA->id,
            'category' => IncidentCategory::TRAY_DISCREPANCY->value,
            'status' => IncidentStatus::OPEN->value,
        ]);
    }

    /**
     * GATE 7: Incident state machine full lifecycle.
     */
    public function test_incident_state_machine_and_role_permissions(): void
    {
        // 1. Create Incident
        $incident = Incident::create([
            'sppg_id' => $this->sppg->id,
            'school_id' => $this->schoolA->id,
            'subject_type' => TrayReturn::class,
            'subject_id' => 1,
            'reported_by' => $this->driver->id,
            'category' => IncidentCategory::TRAY_DISCREPANCY,
            'severity' => IncidentSeverity::MEDIUM,
            'status' => IncidentStatus::OPEN,
            'description' => 'Selisih wadah saat penjemputan sekolah',
        ]);

        // 2. Kepala SPPG Acknowledges
        $ackResponse = $this->actingAs($this->kepalaSppg)->post(route('incidents.transition', [$incident, 'acknowledge']));
        $ackResponse->assertRedirect(route('incidents.show', $incident));
        $incident->refresh();
        $this->assertEquals(IncidentStatus::ACKNOWLEDGED, $incident->status);

        // 3. Asisten starts progress
        $progResponse = $this->actingAs($this->asisten)->post(route('incidents.transition', [$incident, 'start_progress']));
        $progResponse->assertRedirect(route('incidents.show', $incident));
        $incident->refresh();
        $this->assertEquals(IncidentStatus::IN_PROGRESS, $incident->status);

        // 4. Asisten CANNOT resolve (Forbidden)
        $forbiddenResolve = $this->actingAs($this->asisten)->post(route('incidents.transition', [$incident, 'resolve']), [
            'resolution_notes' => 'Catatan penyelesaian oleh asisten',
        ]);
        $forbiddenResolve->assertForbidden();

        // Kepala SPPG resolves
        $resResponse = $this->actingAs($this->kepalaSppg)->post(route('incidents.transition', [$incident, 'resolve']), [
            'resolution_notes' => 'Wadah telah ditemukan di pos satpam sekolah dan diangkut',
        ]);
        $resResponse->assertRedirect(route('incidents.show', $incident));
        $incident->refresh();
        $this->assertEquals(IncidentStatus::RESOLVED, $incident->status);
        $this->assertNotNull($incident->resolved_at);

        // 5. Driver or Asisten CANNOT CLOSE (Only Kepala SPPG can close)
        $forbiddenClose = $this->actingAs($this->asisten)->post(route('incidents.transition', [$incident, 'close']));
        $forbiddenClose->assertForbidden();

        // 6. Kepala SPPG closes incident
        $closeResponse = $this->actingAs($this->kepalaSppg)->post(route('incidents.transition', [$incident, 'close']));
        $closeResponse->assertRedirect(route('incidents.show', $incident));
        $incident->refresh();
        $this->assertEquals(IncidentStatus::CLOSED, $incident->status);

        // 7. Reopen by Kepala SPPG
        $reopenResponse = $this->actingAs($this->kepalaSppg)->post(route('incidents.transition', [$incident, 'reopen']), [
            'reason' => 'Perlu pengecekan ulang nomor seri wadah',
        ]);
        $reopenResponse->assertRedirect(route('incidents.show', $incident));
        $incident->refresh();
        $this->assertEquals(IncidentStatus::REOPENED, $incident->status);
    }

    /**
     * GATE 7 & 1: Integration with TripClosureService - Unresolved incident (OPEN / ACKNOWLEDGED / IN_PROGRESS)
     * blocks Kurir trip closure.
     */
    public function test_unresolved_incident_blocks_driver_trip_closure(): void
    {
        [$trip, $stop, $serviceRecord, $tray] = $this->createDeliveredStopWithTray($this->schoolA, 40);

        // Simulate trip with returned fleet & reconciled trays
        $trip->update([
            'status' => TripStatus::IN_PROGRESS,
            'return_started_at' => Carbon::now()->subMinutes(30),
            'arrived_at_sppg_at' => Carbon::now()->subMinutes(15),
            'total_trays_returned' => 40,
            'total_trays_damaged' => 0,
            'total_trays_missing' => 0,
            'fleet_returned_at' => Carbon::now()->subMinutes(15),
            'actual_return_time' => Carbon::now()->subMinutes(15),
            'final_odometer' => 1250,
        ]);

        // Attach an active incident to the trip stop
        $incident = Incident::create([
            'trip_id' => $trip->id,
            'trip_stop_id' => $stop->id,
            'sppg_id' => $this->sppg->id,
            'school_id' => $this->schoolA->id,
            'subject_type' => TripStop::class,
            'subject_id' => $stop->id,
            'reported_by' => $this->driver->id,
            'category' => IncidentCategory::PORTION_SHORTAGE,
            'severity' => IncidentSeverity::HIGH,
            'status' => IncidentStatus::IN_PROGRESS, // Unresolved!
            'description' => 'Ada porsi tercecer di jalan',
        ]);

        $closureService = app(TripClosureService::class);
        $readiness = $closureService->getClosureReadiness($trip);

        // Should report open incident in readiness exceptions
        $this->assertTrue($readiness['has_open_incidents']);
        $exceptionMessages = collect($readiness['exceptions'])->pluck('message')->implode(' ');
        $this->assertStringContainsString('Terdapat insiden aktif yang belum terselesaikan', $exceptionMessages);

        // Attempting to close as Driver throws ValidationException
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $closureService->closeTrip($trip, $this->driver);
    }

    /**
     * GATE 8: EvidencePhoto Policy ensures isolation for TrayReturn and Incident photos.
     */
    public function test_evidence_photo_policy_isolation_for_tray_returns_and_incidents(): void
    {
        [$trip, $stop, $serviceRecord, $tray] = $this->createDeliveredStopWithTray($this->schoolA, 50);

        // Create evidence photo on TrayReturn
        Storage::disk('local')->put('photos/tray-evidence.jpg', 'fake-image-bytes');
        $trayPhoto = EvidencePhoto::create([
            'evidenceable_type' => TrayReturn::class,
            'evidenceable_id' => $tray->id,
            'evidence_type' => 'TRAY_RETURN_READY',
            'uploaded_by' => $this->picSchoolA->id,
            'file_path' => 'photos/tray-evidence.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'captured_at' => Carbon::now(),
        ]);

        // PIC A can view photo
        $this->actingAs($this->picSchoolA)->get(route('evidence-photos.show', $trayPhoto))->assertOk();

        // PIC B CANNOT view photo (isolated to School A)
        $this->actingAs($this->picSchoolB)->get(route('evidence-photos.show', $trayPhoto))->assertForbidden();

        // Staff from another SPPG CANNOT view photo
        $this->actingAs($this->otherKepalaSppg)->get(route('evidence-photos.show', $trayPhoto))->assertForbidden();
    }
}
