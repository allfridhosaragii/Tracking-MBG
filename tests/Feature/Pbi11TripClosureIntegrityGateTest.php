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
use App\Enums\TripStatus;
use App\Enums\TripStopStatus;
use App\Enums\TripType;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\DailyPortionRequest;
use App\Models\DeliveryItem;
use App\Models\FoodBatch;
use App\Models\Incident;
use App\Models\Menu;
use App\Models\ProductionAllocation;
use App\Models\ProductionPlan;
use App\Models\School;
use App\Models\SchoolServiceRecord;
use App\Models\Sppg;
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

class Pbi11TripClosureIntegrityGateTest extends TestCase
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
            'name' => 'SPPG Unit 2',
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
            'name' => 'Kurir Lain Tenant Sama',
            'email' => 'kurir.lain@mbgtrack.test',
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
            'target_portions' => 600,
            'status' => ProductionPlanStatus::COMPLETED,
            'created_by' => $this->kepalaSppg->id,
        ]);

        $this->batch = FoodBatch::create([
            'production_plan_id' => $this->plan->id,
            'menu_id' => $this->menu->id,
            'batch_code' => 'BATCH-GATE-' . uniqid(),
            'portion_category' => 'REGULER',
            'planned_portions' => 600,
            'actual_portions' => 600,
            'status' => FoodBatchStatus::PACKED,
            'created_by' => $this->kepalaSppg->id,
        ]);
    }

    protected function createHandledTrip(): Trip
    {
        $trip = Trip::create([
            'sppg_id' => $this->sppg->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'service_date' => Carbon::today(),
            'trip_type' => TripType::DELIVERY,
            'status' => TripStatus::IN_PROGRESS,
            'created_by' => $this->kepalaSppg->id,
            'trip_code' => 'TRIP-GATE-' . uniqid(),
            'started_at' => Carbon::now()->subMinutes(60),
            'tracking_status' => 'ONLINE',
            'active_tracking_session_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $portionReq = DailyPortionRequest::firstOrCreate(
            ['school_id' => $this->schoolA->id, 'service_date' => Carbon::today()],
            [
                'sppg_id' => $this->sppg->id,
                'registered_count' => 300,
                'requested_portions' => 300,
                'status' => PortionRequestStatus::CONFIRMED,
                'created_by' => $this->picSchool->id,
                'confirmed_at' => now(),
            ]
        );

        $alloc = ProductionAllocation::firstOrCreate(
            [
                'production_plan_id' => $this->plan->id,
                'portion_request_id' => $portionReq->id,
            ],
            [
                'school_id' => $this->schoolA->id,
                'allocated_portions' => 300,
            ]
        );

        $stop = TripStop::create([
            'trip_id' => $trip->id,
            'school_id' => $this->schoolA->id,
            'sequence_number' => 1,
            'status' => TripStopStatus::COMPLETED,
            'planned_portions' => 300,
            'loaded_portions' => 300,
            'arrived_at' => Carbon::now()->subMinutes(40),
            'completed_at' => Carbon::now()->subMinutes(30),
        ]);

        DeliveryItem::create([
            'trip_stop_id' => $stop->id,
            'food_batch_id' => $this->batch->id,
            'production_allocation_id' => $alloc->id,
            'planned_portions' => 300,
            'loaded_portions' => 300,
            'delivered_portions' => 300,
            'status' => 'LOADED',
        ]);

        return $trip;
    }

    /**
     * 1. GATE A: Semua stop selesai tetapi belum mulai pulang: closure ditolak.
     */
    public function test_closure_is_rejected_when_return_to_depot_not_started(): void
    {
        $trip = $this->createHandledTrip();
        $this->assertNull($trip->return_started_at);
        $this->assertNull($trip->arrived_at_sppg_at);

        // Kurir mencoba menutup trip secara langsung
        $response = $this->actingAs($this->driver)
            ->post(route('driver.trips.complete', $trip));

        $response->assertSessionHasErrors(['return_started_at']);
        $trip->refresh();
        $this->assertEquals(TripStatus::IN_PROGRESS, $trip->status);
        $this->assertNull($trip->return_started_at);
        $this->assertNull($trip->arrived_at_sppg_at);
    }

    /**
     * 2. GATE A: Semua stop selesai, sudah mulai pulang tetapi belum tiba di SPPG: closure ditolak.
     */
    public function test_closure_is_rejected_when_returning_but_not_arrived_at_depot(): void
    {
        $trip = $this->createHandledTrip();
        $trip->update([
            'return_started_at' => Carbon::now()->subMinutes(15),
            'arrived_at_sppg_at' => null,
            'total_trays_returned' => 30,
        ]);

        $response = $this->actingAs($this->driver)
            ->post(route('driver.trips.complete', $trip));

        $response->assertSessionHasErrors(['arrived_at_sppg_at']);
        $trip->refresh();
        $this->assertEquals(TripStatus::IN_PROGRESS, $trip->status);
        $this->assertNull($trip->arrived_at_sppg_at);
    }

    /**
     * 3. GATE C: Sudah tiba di SPPG tanpa rekonsiliasi sah: closure ditolak.
     */
    public function test_closure_is_rejected_when_arrived_at_depot_without_valid_reconciliation(): void
    {
        $trip = $this->createHandledTrip();
        $trip->update([
            'return_started_at' => Carbon::now()->subMinutes(20),
            'arrived_at_sppg_at' => Carbon::now()->subMinutes(5),
            'total_trays_returned' => null, // UNKNOWN / belum direkonsiliasi
        ]);

        $response = $this->actingAs($this->driver)
            ->post(route('driver.trips.complete', $trip));

        $response->assertSessionHasErrors(['total_trays_returned']);
        $trip->refresh();
        $this->assertEquals(TripStatus::IN_PROGRESS, $trip->status);
    }

    /**
     * 4. GATE B: Kedatangan depot tidak diam-diam membuat waktu mulai pulang palsu.
     */
    public function test_depot_arrival_does_not_fabricate_fake_return_started_timestamp(): void
    {
        $trip = $this->createHandledTrip();
        $this->assertNull($trip->return_started_at);

        // Mencoba konfirmasi tiba tanpa memulai kepulangan terlebih dahulu
        $response = $this->actingAs($this->driver)
            ->post(route('driver.trips.arrive-depot', $trip));

        $response->assertSessionHasErrors(['return_started_at']);
        $trip->refresh();

        // Verifikasi tidak ada timestamp fiktif yang diciptakan
        $this->assertNull($trip->return_started_at);
        $this->assertNull($trip->arrived_at_sppg_at);
    }

    /**
     * 5. GATE C: Rekonsiliasi sebelum tahapan yang diizinkan ditolak.
     */
    public function test_reconciliation_before_depot_arrival_is_rejected(): void
    {
        $trip = $this->createHandledTrip();
        $trip->update([
            'return_started_at' => Carbon::now()->subMinutes(10),
            'arrived_at_sppg_at' => null, // Belum tiba di SPPG
        ]);

        $response = $this->actingAs($this->driver)
            ->post(route('driver.trips.reconcile-trays', $trip), [
                'trays_returned' => 30,
            ]);

        $response->assertSessionHasErrors(['arrived_at_sppg_at']);
        $trip->refresh();
        $this->assertNull($trip->total_trays_returned);
    }

    /**
     * 6. GATE D: Rekonsiliasi setelah COMPLETED ditolak (immutability).
     */
    public function test_reconciliation_after_trip_completed_is_rejected_as_immutable(): void
    {
        $trip = $this->createHandledTrip();
        $trip->update([
            'status' => TripStatus::COMPLETED,
            'return_started_at' => Carbon::now()->subMinutes(30),
            'arrived_at_sppg_at' => Carbon::now()->subMinutes(15),
            'total_trays_returned' => 30,
            'total_trays_damaged' => 0,
            'total_trays_missing' => 0,
            'completed_at' => Carbon::now()->subMinutes(10),
        ]);

        $response = $this->actingAs($this->driver)
            ->post(route('driver.trips.reconcile-trays', $trip), [
                'trays_returned' => 25,
                'trays_damaged' => 5,
            ]);

        $response->assertSessionHasErrors(['status']);
        $trip->refresh();
        $this->assertEquals(30, $trip->total_trays_returned);
        $this->assertEquals(0, $trip->total_trays_damaged);
    }

    /**
     * 7. GATE C: Nilai null tidak dianggap 0 atau rekonsiliasi lengkap; angka 0 adalah hitungan fisik sah.
     */
    public function test_null_tray_counts_are_distinguished_from_zero_count(): void
    {
        $trip = $this->createHandledTrip();
        $trip->update([
            'return_started_at' => Carbon::now()->subMinutes(25),
            'arrived_at_sppg_at' => Carbon::now()->subMinutes(10),
        ]);

        // Rekonsiliasi tanpa input (null) ditolak
        $emptyResp = $this->actingAs($this->driver)
            ->post(route('driver.trips.reconcile-trays', $trip), []);

        $emptyResp->assertSessionHasErrors(['trays_returned']);
        $trip->refresh();
        $this->assertNull($trip->total_trays_returned);

        // Rekonsiliasi dengan angka 0 sah (misal 0 kembali, 30 hilang)
        $zeroResp = $this->actingAs($this->driver)
            ->post(route('driver.trips.reconcile-trays', $trip), [
                'trays_returned' => 0,
                'trays_damaged' => 0,
                'trays_missing' => 30,
                'notes' => 'Seluruh wadah hilang di lokasi pengungsian.',
            ]);

        $zeroResp->assertRedirect(route('driver.trips.active', $trip));
        $trip->refresh();
        $this->assertSame(0, $trip->total_trays_returned);
        $this->assertSame(30, $trip->total_trays_missing);
    }

    /**
     * 8. GATE A & C: Rekonsiliasi sah diikuti closure sah berhasil.
     */
    public function test_legitimate_return_arrival_reconciliation_and_closure_workflow(): void
    {
        $trip = $this->createHandledTrip();

        // Step 1: Mulai kepulangan
        $retResp = $this->actingAs($this->driver)
            ->post(route('driver.trips.return', $trip), ['notes' => 'Armada mulai pulang']);
        $retResp->assertRedirect();
        $trip->refresh();
        $this->assertNotNull($trip->return_started_at);

        // Step 2: Konfirmasi tiba di SPPG
        $arrResp = $this->actingAs($this->driver)
            ->post(route('driver.trips.arrive-depot', $trip));
        $arrResp->assertRedirect();
        $trip->refresh();
        $this->assertNotNull($trip->arrived_at_sppg_at);

        // Step 3: Rekonsiliasi ompreng (30 kembali baik, 0 rusak, 0 hilang)
        $recResp = $this->actingAs($this->driver)
            ->post(route('driver.trips.reconcile-trays', $trip), [
                'trays_returned' => 30,
                'trays_damaged' => 0,
                'trays_missing' => 0,
            ]);
        $recResp->assertRedirect();
        $trip->refresh();
        $this->assertSame(30, $trip->total_trays_returned);

        // Step 4: Final closure oleh kurir (tidak ada insiden terbuka)
        $clsResp = $this->actingAs($this->driver)
            ->post(route('driver.trips.complete', $trip));
        $clsResp->assertRedirect();
        $trip->refresh();
        $this->assertEquals(TripStatus::COMPLETED, $trip->status);
        $this->assertEquals($this->driver->id, $trip->closed_by);
        $this->assertEquals('OFFLINE', $trip->tracking_status);
    }

    /**
     * 9. GATE E: Duplicate reconciliation tidak membuat duplicate incidents.
     */
    public function test_duplicate_reconciliation_does_not_create_duplicate_open_incidents(): void
    {
        $trip = $this->createHandledTrip();
        $trip->update([
            'return_started_at' => Carbon::now()->subMinutes(20),
            'arrived_at_sppg_at' => Carbon::now()->subMinutes(5),
        ]);

        // Submit rekonsiliasi pertama dengan kerusakan
        $this->actingAs($this->driver)
            ->post(route('driver.trips.reconcile-trays', $trip), [
                'trays_returned' => 27,
                'trays_damaged' => 2,
                'trays_missing' => 1,
            ]);

        $incidentCountFirst = Incident::where('subject_type', Trip::class)
            ->where('subject_id', $trip->id)
            ->where('category', IncidentCategory::TRAY_DISCREPANCY)
            ->count();
        $this->assertSame(1, $incidentCountFirst);

        // Submit ulang / retry rekonsiliasi kedua
        $this->actingAs($this->driver)
            ->post(route('driver.trips.reconcile-trays', $trip), [
                'trays_returned' => 26,
                'trays_damaged' => 3,
                'trays_missing' => 1,
            ]);

        $incidentCountSecond = Incident::where('subject_type', Trip::class)
            ->where('subject_id', $trip->id)
            ->where('category', IncidentCategory::TRAY_DISCREPANCY)
            ->count();

        // Wajib tetap 1 tiket insiden (deduplikasi), bukan 2 tiket
        $this->assertSame(1, $incidentCountSecond);

        $incident = Incident::where('subject_type', Trip::class)
            ->where('subject_id', $trip->id)
            ->where('category', IncidentCategory::TRAY_DISCREPANCY)
            ->first();
        $this->assertStringContainsString('3 rusak', $incident->description);
    }

    /**
     * 10. GATE E: Incident OPEN tidak hilang atau otomatis terhapus/ditutup saat koreksi jumlah ke 0.
     */
    public function test_open_incident_remains_open_when_discrepancy_counts_are_corrected_to_zero(): void
    {
        $trip = $this->createHandledTrip();
        $trip->update([
            'return_started_at' => Carbon::now()->subMinutes(20),
            'arrived_at_sppg_at' => Carbon::now()->subMinutes(5),
        ]);

        // Step 1: Muncul insiden selisih
        $this->actingAs($this->driver)
            ->post(route('driver.trips.reconcile-trays', $trip), [
                'trays_returned' => 28,
                'trays_damaged' => 2,
                'trays_missing' => 0,
            ]);

        $incident = Incident::where('subject_type', Trip::class)
            ->where('subject_id', $trip->id)
            ->where('status', IncidentStatus::OPEN)
            ->firstOrFail();

        // Step 2: Kurir mengoreksi jumlah damaged ke 0
        $this->actingAs($this->driver)
            ->post(route('driver.trips.reconcile-trays', $trip), [
                'trays_returned' => 30,
                'trays_damaged' => 0,
                'trays_missing' => 0,
            ]);

        // Insiden TIDAK boleh otomatis hilang atau berstatus RESOLVED tanpa pengesahan resmi
        $incident->refresh();
        $this->assertEquals(IncidentStatus::OPEN, $incident->status);
        $this->assertStringContainsString('Koreksi hitungan fisik', $incident->description);
    }

    /**
     * 11. GATE D: Concurrent closure menghasilkan satu event penutupan sah (idempotent).
     */
    public function test_concurrent_or_repeated_closure_is_idempotent(): void
    {
        $trip = $this->createHandledTrip();
        $trip->update([
            'return_started_at' => Carbon::now()->subMinutes(30),
            'arrived_at_sppg_at' => Carbon::now()->subMinutes(10),
            'total_trays_returned' => 30,
            'total_trays_damaged' => 0,
            'total_trays_missing' => 0,
        ]);

        // Closure pertama
        $this->actingAs($this->driver)
            ->post(route('driver.trips.complete', $trip));

        $trip->refresh();
        $this->assertEquals(TripStatus::COMPLETED, $trip->status);
        $firstCompletedAt = $trip->completed_at;

        // Closure kedua (retry / concurrent)
        $this->travel(2)->minutes();
        $this->actingAs($this->driver)
            ->post(route('driver.trips.complete', $trip));

        $trip->refresh();
        $this->assertEquals(TripStatus::COMPLETED, $trip->status);
        $this->assertEquals($firstCompletedAt->toIso8601String(), $trip->completed_at->toIso8601String());

        // Verifikasi log activity trip.completed tepat 1 record
        $logCount = ActivityLog::where('auditable_type', Trip::class)
            ->where('auditable_id', $trip->id)
            ->where('event', 'trip.completed')
            ->count();
        $this->assertSame(1, $logCount);
    }

    /**
     * 12 & 13. GATE A: Closure menutup sesi GPS aktif dan telemetry setelah closure ditolak.
     */
    public function test_closure_terminates_gps_and_rejects_subsequent_telemetry(): void
    {
        $trip = $this->createHandledTrip();
        $trip->update([
            'return_started_at' => Carbon::now()->subMinutes(30),
            'arrived_at_sppg_at' => Carbon::now()->subMinutes(10),
            'total_trays_returned' => 30,
            'total_trays_damaged' => 0,
            'total_trays_missing' => 0,
        ]);

        $this->actingAs($this->driver)
            ->post(route('driver.trips.complete', $trip));

        $trip->refresh();
        $this->assertNull($trip->active_tracking_session_id);
        $this->assertEquals('OFFLINE', $trip->tracking_status);
        $this->assertNotNull($trip->tracking_ended_at);

        // Telemetri baru setelah penutupan wajib ditolak
        $response = $this->actingAs($this->driver)
            ->postJson(route('driver.trips.telemetry', $trip), [
                'latitude' => -7.2885,
                'longitude' => 112.7385,
                'accuracy' => 10,
                'recorded_at' => Carbon::now()->toIso8601String(),
            ]);

        $response->assertStatus(422);
    }

    /**
     * 14. GATE C: Mode B PBI-10 tetap menjaga nullable attribution (tidak dipaksa 0).
     */
    public function test_mode_b_nullable_attribution_preserved_on_closure(): void
    {
        $trip = $this->createHandledTrip();
        $stop = $trip->stops->first();

        SchoolServiceRecord::create([
            'trip_stop_id' => $stop->id,
            'received_by' => $this->picSchool->id,
            'received_at' => Carbon::now()->subMinutes(20),
            'expected_portions' => 300,
            'received_portions' => 300,
            'status' => SchoolReceiptStatus::RECEIVED,
            'notes' => 'Mode B serah terima darurat',
            'created_at' => Carbon::now()->subMinutes(20),
        ]);

        $deliveryItem = $stop->deliveryItems->first();
        $deliveryItem->update(['delivered_portions' => null]);

        $trip->update([
            'return_started_at' => Carbon::now()->subMinutes(25),
            'arrived_at_sppg_at' => Carbon::now()->subMinutes(10),
            'total_trays_returned' => 20,
            'total_trays_damaged' => 0,
            'total_trays_missing' => 0,
        ]);

        $this->actingAs($this->driver)
            ->post(route('driver.trips.complete', $trip));

        $trip->refresh();
        $this->assertEquals(TripStatus::COMPLETED, $trip->status);

        $deliveryItem->refresh();
        $this->assertNull($deliveryItem->delivered_portions, 'delivered_portions pada Mode B wajib tetap null.');
    }

    /**
     * 15. GATE F: Role unauthorized, cross-tenant, kurir saat ada insiden terbuka, dan self-approval ditolak.
     */
    public function test_authority_validation_and_separation_of_duties(): void
    {
        $trip = $this->createHandledTrip();
        $trip->update([
            'return_started_at' => Carbon::now()->subMinutes(20),
            'arrived_at_sppg_at' => Carbon::now()->subMinutes(5),
            'total_trays_returned' => 30,
        ]);

        // A. Cross-tenant Kepala SPPG ditolak (403)
        $this->actingAs($this->otherKepalaSppg)
            ->post(route('head.distribution.trips.close', $trip))
            ->assertForbidden();

        // B. Driver lain pada SPPG yang sama ditolak (403)
        $this->actingAs($this->otherDriver)
            ->post(route('driver.trips.complete', $trip))
            ->assertForbidden();

        // C. PIC Sekolah ditolak (403)
        $this->actingAs($this->picSchool)
            ->post(route('driver.trips.complete', $trip))
            ->assertForbidden();

        // D. Jika ada insiden OPEN: Kurir TIDAK BOLEH menutup perjalanan (403/Forbidden)
        Incident::create([
            'sppg_id' => $trip->sppg_id,
            'subject_type' => Trip::class,
            'subject_id' => $trip->id,
            'reported_by' => $this->driver->id,
            'category' => IncidentCategory::TRAY_DISCREPANCY,
            'severity' => IncidentSeverity::MEDIUM,
            'status' => IncidentStatus::OPEN,
            'description' => 'Terdapat selisih ompreng 2 hilang.',
        ]);

        $kurirWithIncidentResp = $this->actingAs($this->driver)
            ->post(route('driver.trips.complete', $trip));
        $kurirWithIncidentResp->assertForbidden();

        // E. Kepala SPPG dari tenant yang sama berwenang menutup trip dengan insiden terbuka (selama bukan driver)
        $headCloseResp = $this->actingAs($this->kepalaSppg)
            ->post(route('head.distribution.trips.close', $trip), [
                'completion_notes' => 'Pengesahan administratif Kepala SPPG atas insiden terbuka.',
            ]);
        $headCloseResp->assertRedirect(route('trips.show', $trip));

        $trip->refresh();
        $this->assertEquals(TripStatus::COMPLETED, $trip->status);
        $this->assertEquals($this->kepalaSppg->id, $trip->closed_by);
    }

    /**
     * 16. GATE F: Kepala SPPG yang bertindak sebagai kurir tidak boleh self-approval jika ada insiden terbuka.
     */
    public function test_kepala_sppg_cannot_self_close_trip_with_open_incidents_if_acting_as_driver(): void
    {
        $trip = $this->createHandledTrip();
        // Set kepalaSppg as the driver of the trip
        $trip->update([
            'driver_id' => $this->kepalaSppg->id,
            'return_started_at' => Carbon::now()->subMinutes(20),
            'arrived_at_sppg_at' => Carbon::now()->subMinutes(5),
            'total_trays_returned' => 30,
        ]);

        Incident::create([
            'sppg_id' => $trip->sppg_id,
            'subject_type' => Trip::class,
            'subject_id' => $trip->id,
            'reported_by' => $this->kepalaSppg->id,
            'category' => IncidentCategory::TRAY_DISCREPANCY,
            'severity' => IncidentSeverity::HIGH,
            'status' => IncidentStatus::OPEN,
            'description' => 'Selisih wadah 5 hilang saat diantar oleh Kepala SPPG.',
        ]);

        // Self-approval must be forbidden
        $this->actingAs($this->kepalaSppg)
            ->post(route('head.distribution.trips.close', $trip))
            ->assertForbidden();

        $trip->refresh();
        $this->assertEquals(TripStatus::IN_PROGRESS, $trip->status);
    }
}
