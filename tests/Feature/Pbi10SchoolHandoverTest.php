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
use App\Models\DeliveryItem;
use App\Models\EvidencePhoto;
use App\Models\FoodBatch;
use App\Models\Menu;
use App\Models\ProductionPlan;
use App\Models\QualityCheck;
use App\Models\School;
use App\Models\SchoolServiceRecord;
use App\Models\Sppg;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\SchoolHandoverService;
use Carbon\Carbon;
use Database\Seeders\MbgTrackBaselineSeeder;
use Database\Seeders\MenuBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Pbi10SchoolHandoverTest extends TestCase
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
    protected TripStop $stop1;
    protected TripStop $stop2;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');

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
            'batch_code' => 'BATCH-P10-' . uniqid(),
            'planned_portions' => 800,
            'actual_portions' => 800,
            'status' => FoodBatchStatus::RELEASED,
            'created_by' => User::where('role', UserRole::ASISTEN_LAPANGAN)->first()->id,
        ]);

        // Buat Trip Multi-stop untuk Kurir
        $this->trip = Trip::create([
            'sppg_id' => $this->sppg->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'service_date' => Carbon::today(),
            'trip_type' => TripType::DELIVERY,
            'status' => TripStatus::IN_PROGRESS,
            'created_by' => User::where('role', UserRole::KEPALA_SPPG)->first()->id,
            'trip_code' => 'TRIP-P10-' . uniqid(),
            'started_at' => Carbon::now()->subMinutes(30),
            'tracking_status' => 'ONLINE',
            'active_tracking_session_id' => (string) \Illuminate\Support\Str::uuid(),
            'last_latitude' => -7.9820,
            'last_longitude' => 112.6310,
            'last_location_at' => Carbon::now(),
        ]);

        // Stop 1: School A (340 porsi)
        $this->stop1 = TripStop::create([
            'trip_id' => $this->trip->id,
            'school_id' => $this->schoolA->id,
            'sequence_number' => 1,
            'status' => TripStopStatus::EN_ROUTE,
            'planned_portions' => 340,
            'loaded_portions' => 340,
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
            'trip_stop_id' => $this->stop1->id,
            'food_batch_id' => $this->batch->id,
            'production_allocation_id' => $alloc1->id,
            'planned_portions' => 340,
            'loaded_portions' => 340,
        ]);

        // Stop 2: School B (250 porsi)
        $this->stop2 = TripStop::create([
            'trip_id' => $this->trip->id,
            'school_id' => $this->schoolB->id,
            'sequence_number' => 2,
            'status' => TripStopStatus::PENDING,
            'planned_portions' => 250,
            'loaded_portions' => 250,
        ]);

        $req2 = \App\Models\DailyPortionRequest::create([
            'school_id' => $this->schoolB->id,
            'sppg_id' => $this->sppg->id,
            'service_date' => Carbon::today(),
            'registered_count' => 250,
            'requested_portions' => 250,
            'status' => \App\Enums\PortionRequestStatus::CONFIRMED,
            'created_by' => $this->picSchoolB->id,
            'confirmed_at' => now(),
        ]);

        $alloc2 = \App\Models\ProductionAllocation::create([
            'production_plan_id' => $this->plan->id,
            'portion_request_id' => $req2->id,
            'school_id' => $this->schoolB->id,
            'allocated_portions' => 250,
        ]);

        DeliveryItem::create([
            'trip_stop_id' => $this->stop2->id,
            'food_batch_id' => $this->batch->id,
            'production_allocation_id' => $alloc2->id,
            'planned_portions' => 250,
            'loaded_portions' => 250,
        ]);
    }

    /**
     * Test 1: Kurir menandai tiba di sekolah (Page D03 / D04).
     */
    public function test_courier_marks_arrival_and_records_arrival_timestamp(): void
    {
        $response = $this->actingAs($this->driver)
            ->post(route('driver.trips.stops.arrive', [$this->trip, $this->stop1]));

        $response->assertRedirect(route('driver.trips.active', $this->trip));

        $this->stop1->refresh();
        $this->assertEquals(TripStopStatus::ARRIVED_AWAITING_CONFIRMATION, $this->stop1->status);
        $this->assertNotNull($this->stop1->arrived_at);

        // Record service record terinisialisasi dalam status WAITING
        $this->assertDatabaseHas('school_service_records', [
            'trip_stop_id' => $this->stop1->id,
            'status' => SchoolReceiptStatus::WAITING->value,
            'expected_portions' => 340,
        ]);
    }

    /**
     * Test 2: Kurir mengunggah bukti foto serah terima di sekolah (Page D04).
     */
    public function test_courier_uploads_handover_photo_evidence(): void
    {
        $file = UploadedFile::fake()->image('handover_photo.jpg', 800, 600);

        $response = $this->actingAs($this->driver)
            ->post(route('driver.trips.stops.handover-evidence', [$this->trip, $this->stop1]), [
                'photo' => $file,
                'notes' => 'Diserahkan langsung ke ruang guru SDN 01.',
            ]);

        $response->assertSessionHas('success');

        $this->stop1->refresh();
        $this->assertEquals(TripStopStatus::ARRIVED_AWAITING_CONFIRMATION, $this->stop1->status);

        $this->assertDatabaseHas('evidence_photos', [
            'evidenceable_type' => TripStop::class,
            'evidenceable_id' => $this->stop1->id,
            'evidence_type' => 'SCHOOL_HANDOVER',
            'uploaded_by' => $this->driver->id,
            'notes' => 'Diserahkan langsung ke ruang guru SDN 01.',
        ]);
    }

    /**
     * Test 3: PIC Sekolah lain tidak dapat memverifikasi atau mengonfirmasi penerimaan (Tenant Isolation 403).
     */
    public function test_pic_from_different_school_cannot_confirm_receipt(): void
    {
        // Kurir tiba di Stop 1 (School A)
        $this->stop1->update([
            'status' => TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
            'arrived_at' => Carbon::now(),
        ]);

        // PIC School B mencoba mengakses form dan konfirmasi Stop 1 milik School A
        $responseShow = $this->actingAs($this->picSchoolB)
            ->get(route('school.receipts.show', $this->stop1));
        $responseShow->assertStatus(403);

        $responseConfirm = $this->actingAs($this->picSchoolB)
            ->post(route('school.receipts.confirm', $this->stop1), [
                'received_portions' => 340,
                'damaged_portions' => 0,
                'appearance_status' => 'FRESH',
                'aroma_status' => 'NORMAL',
                'texture_status' => 'NORMAL',
                'organoleptic_result' => 'SAFE',
            ]);
        $responseConfirm->assertStatus(403);
    }

    /**
     * Test 4: Kurir tidak dapat melakukan self-confirm penerimaan (Separation of Duties 403).
     */
    public function test_courier_cannot_self_confirm_receipt(): void
    {
        $this->stop1->update([
            'status' => TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
            'arrived_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->driver)
            ->post(route('school.receipts.confirm', $this->stop1), [
                'received_portions' => 340,
                'damaged_portions' => 0,
                'appearance_status' => 'FRESH',
                'aroma_status' => 'NORMAL',
                'texture_status' => 'NORMAL',
                'organoleptic_result' => 'SAFE',
            ]);

        // Route dilindungi middleware role:PIC_SEKOLAH & Policy
        $response->assertStatus(403);
    }

    /**
     * Test 5: Tidak dapat konfirmasi penerimaan sebelum armada tiba di sekolah.
     */
    public function test_cannot_confirm_receipt_before_driver_marks_arrival(): void
    {
        // Status masih EN_ROUTE (belum tiba)
        $this->stop1->update([
            'status' => TripStopStatus::EN_ROUTE,
            'arrived_at' => null,
        ]);

        $response = $this->actingAs($this->picSchoolA)
            ->post(route('school.receipts.confirm', $this->stop1), [
                'received_portions' => 340,
                'damaged_portions' => 0,
                'appearance_ok' => 1,
                'aroma_ok' => 1,
                'texture_ok' => 1,
                'taste_tested' => 1,
                'taste_ok' => 1,
                'conclusion' => 'SAFE',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test 6: Validasi Form Request (422 bila input invalid).
     */
    public function test_confirm_receipt_validation_rules(): void
    {
        $this->stop1->update([
            'status' => TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
            'arrived_at' => Carbon::now(),
        ]);

        // A. Missing required fields
        $response = $this->actingAs($this->picSchoolA)
            ->post(route('school.receipts.confirm', $this->stop1), []);
        $response->assertSessionHasErrors(['received_portions', 'appearance_ok', 'aroma_ok', 'texture_ok', 'taste_tested']);

        // B. Damaged portions melebihi received portions
        $response = $this->actingAs($this->picSchoolA)
            ->post(route('school.receipts.confirm', $this->stop1), [
                'received_portions' => 100,
                'damaged_portions' => 150,
                'appearance_ok' => 1,
                'aroma_ok' => 1,
                'texture_ok' => 1,
                'taste_tested' => 1,
                'taste_ok' => 1,
                'conclusion' => 'SAFE',
            ]);
        $response->assertSessionHasErrors(['damaged_portions']);

        // C. taste_tested false tanpa taste_skipped_reason
        $response = $this->actingAs($this->picSchoolA)
            ->post(route('school.receipts.confirm', $this->stop1), [
                'received_portions' => 100,
                'damaged_portions' => 0,
                'appearance_ok' => 1,
                'aroma_ok' => 1,
                'texture_ok' => 1,
                'taste_tested' => 0,
                'taste_skipped_reason' => null,
                'conclusion' => 'SAFE',
            ]);
        $response->assertSessionHasErrors(['taste_skipped_reason']);
    }

    /**
     * Test 7: Happy Path — Konfirmasi penerimaan penuh tanpa selisih (RECEIVED).
     */
    public function test_confirm_receipt_happy_path_exact_portions(): void
    {
        $this->stop1->update([
            'status' => TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
            'arrived_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->picSchoolA)
            ->post(route('school.receipts.confirm', $this->stop1), [
                'received_portions' => 340,
                'damaged_portions' => 0,
                'appearance_ok' => 1,
                'aroma_ok' => 1,
                'texture_ok' => 1,
                'taste_tested' => 1,
                'taste_ok' => 1,
                'temperature_c' => 62.5,
                'conclusion' => 'SAFE',
                'notes' => 'Penerimaan lengkap dalam kondisi hangat.',
            ]);

        $response->assertRedirect(route('school.receipts.show', $this->stop1));
        $response->assertSessionHas('success');

        $this->stop1->refresh();
        $this->assertEquals(TripStopStatus::HANDED_OVER, $this->stop1->status);

        $serviceRecord = $this->stop1->schoolServiceRecord;
        $this->assertNotNull($serviceRecord);
        $this->assertEquals(SchoolReceiptStatus::RECEIVED, $serviceRecord->status);
        $this->assertEquals(340, $serviceRecord->expected_portions);
        $this->assertEquals(340, $serviceRecord->received_portions);
        $this->assertEquals(0, $serviceRecord->damaged_portions);
        $this->assertEquals(0, $serviceRecord->shortage_portions);
        $this->assertEquals($this->picSchoolA->id, $serviceRecord->received_by);

        // QualityCheck kedatangan terbuat dengan benar
        $qc = QualityCheck::where('trip_stop_id', $this->stop1->id)
            ->where('check_stage', QualityCheckStage::ON_ARRIVAL)
            ->first();
        $this->assertNotNull($qc);
        $this->assertTrue($qc->isSafe());
        $this->assertEquals(QualityCheckConclusion::SAFE, $qc->conclusion);
        $this->assertEquals(62.5, (float) $qc->temperature_c);
    }

    /**
     * Test 8: Konfirmasi penerimaan dengan selisih kurang dan porsi rusak (RECEIVED_WITH_DISCREPANCY).
     */
    public function test_confirm_receipt_with_shortage_and_damage_discrepancy(): void
    {
        $this->stop1->update([
            'status' => TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
            'arrived_at' => Carbon::now(),
        ]);

        $evidencePhoto = UploadedFile::fake()->image('damaged_ompreng.jpg', 640, 480);

        // Expected 340, Diterima 338, Rusak 2 -> Selisih kurang = 2 (max(0, 340 - 338))
        $response = $this->actingAs($this->picSchoolA)
            ->post(route('school.receipts.confirm', $this->stop1), [
                'received_portions' => 338,
                'damaged_portions' => 2,
                'appearance_ok' => 1,
                'aroma_ok' => 1,
                'texture_ok' => 1,
                'taste_tested' => 1,
                'taste_ok' => 1,
                'temperature_c' => 61.0,
                'conclusion' => 'SAFE',
                'evidence_photo' => $evidencePhoto,
                'notes' => 'Ada 2 ompreng pecah tutupnya di perjalanan.',
            ]);

        $response->assertRedirect(route('school.receipts.show', $this->stop1));

        $this->stop1->refresh();
        $this->assertEquals(TripStopStatus::HANDED_OVER, $this->stop1->status);

        $serviceRecord = $this->stop1->schoolServiceRecord;
        $this->assertEquals(SchoolReceiptStatus::RECEIVED_WITH_DISCREPANCY, $serviceRecord->status);
        $this->assertEquals(340, $serviceRecord->expected_portions);
        $this->assertEquals(338, $serviceRecord->received_portions);
        $this->assertEquals(2, $serviceRecord->damaged_portions);
        $this->assertEquals(2, $serviceRecord->shortage_portions);

        // Foto bukti deviasi tersimpan
        $this->assertDatabaseHas('evidence_photos', [
            'evidenceable_type' => SchoolServiceRecord::class,
            'evidenceable_id' => $serviceRecord->id,
            'evidence_type' => 'RECEIPT_CONFIRMATION',
            'uploaded_by' => $this->picSchoolA->id,
        ]);
    }

    /**
     * Test 9: Organoleptik UNSAFE membekukan status penerimaan menjadi HELD/REJECTED demi keamanan pangan.
     */
    public function test_confirm_receipt_unsafe_organoleptic_triggers_food_safety_hold(): void
    {
        $this->stop1->update([
            'status' => TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
            'arrived_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->picSchoolA)
            ->post(route('school.receipts.confirm', $this->stop1), [
                'received_portions' => 340,
                'damaged_portions' => 0,
                'appearance_ok' => 0,
                'aroma_ok' => 0,
                'texture_ok' => 0,
                'taste_tested' => 0,
                'taste_skipped_reason' => 'Makanan berlendir dan berbau asam basi; berbahaya bila dicicipi.',
                'temperature_c' => 32.0, // Suhu drop drastis di zona bahaya
                'conclusion' => 'UNSAFE',
                'notes' => 'Makanan rusak dan terindikasi basi saat tiba di sekolah.',
            ]);

        $response->assertRedirect(route('school.receipts.show', $this->stop1));

        $this->stop1->refresh();
        $this->assertEquals(TripStopStatus::EXCEPTION, $this->stop1->status);

        $serviceRecord = $this->stop1->schoolServiceRecord;
        $this->assertEquals(SchoolReceiptStatus::HELD, $serviceRecord->status);

        $qc = QualityCheck::where('trip_stop_id', $this->stop1->id)
            ->where('check_stage', QualityCheckStage::ON_ARRIVAL)
            ->first();
        $this->assertNotNull($qc);
        $this->assertFalse($qc->isSafe());
        $this->assertEquals(QualityCheckConclusion::UNSAFE, $qc->conclusion);
    }

    /**
     * Test 10: Pengesahan Stop 1 otomatis mengaktifkan Stop 2 menjadi EN_ROUTE.
     */
    public function test_receipt_confirmation_activates_subsequent_stop(): void
    {
        $this->assertEquals(TripStopStatus::PENDING, $this->stop2->status);

        $this->stop1->update([
            'status' => TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
            'arrived_at' => Carbon::now(),
        ]);

        $this->actingAs($this->picSchoolA)
            ->post(route('school.receipts.confirm', $this->stop1), [
                'received_portions' => 340,
                'damaged_portions' => 0,
                'appearance_ok' => 1,
                'aroma_ok' => 1,
                'texture_ok' => 1,
                'taste_tested' => 1,
                'taste_ok' => 1,
                'conclusion' => 'SAFE',
            ]);

        $this->stop1->refresh();
        $this->stop2->refresh();

        $this->assertEquals(TripStopStatus::HANDED_OVER, $this->stop1->status);
        $this->assertEquals(TripStopStatus::EN_ROUTE, $this->stop2->status);
    }

    /**
     * Test 11: Idempotensi — Klik ganda submit tidak menduplikasi data atau merusak perhitungan.
     */
    public function test_double_submit_receipt_confirmation_is_idempotent(): void
    {
        $this->stop1->update([
            'status' => TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
            'arrived_at' => Carbon::now(),
        ]);

        $payload = [
            'received_portions' => 340,
            'damaged_portions' => 0,
            'appearance_ok' => 1,
            'aroma_ok' => 1,
            'texture_ok' => 1,
            'taste_tested' => 1,
            'taste_ok' => 1,
            'conclusion' => 'SAFE',
        ];

        // Submit pertama
        $res1 = $this->actingAs($this->picSchoolA)
            ->post(route('school.receipts.confirm', $this->stop1), $payload);
        $res1->assertSessionHas('success');

        $recordCountAfterFirst = SchoolServiceRecord::where('trip_stop_id', $this->stop1->id)->count();
        $this->assertEquals(1, $recordCountAfterFirst);

        // Submit kedua (misal pengguna klik dua kali)
        $res2 = $this->actingAs($this->picSchoolA)
            ->post(route('school.receipts.confirm', $this->stop1), $payload);
        $res2->assertSessionHas('success');

        $recordCountAfterSecond = SchoolServiceRecord::where('trip_stop_id', $this->stop1->id)->count();
        $this->assertEquals(1, $recordCountAfterSecond);
    }

    /**
     * Test 12: Endpoint Map Data menampilkan status stop aktif akurat hingga serah terima selesai.
     */
    public function test_trip_map_data_reflects_active_stop_until_handed_over(): void
    {
        $this->stop1->update([
            'status' => TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
            'arrived_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->driver)
            ->get(route('trips.map-data', $this->trip));

        $response->assertOk();
        $json = $response->json();

        $this->assertEquals($this->stop1->id, $json['active_stop_id']);
        $this->assertCount(2, $json['stops']);

        // Selesaikan stop 1
        $this->actingAs($this->picSchoolA)
            ->post(route('school.receipts.confirm', $this->stop1), [
                'received_portions' => 340,
                'damaged_portions' => 0,
                'appearance_ok' => 1,
                'aroma_ok' => 1,
                'texture_ok' => 1,
                'taste_tested' => 1,
                'taste_ok' => 1,
                'conclusion' => 'SAFE',
            ]);

        // Cek kembali map data: active stop harus berpindah ke Stop 2
        $responseAfter = $this->actingAs($this->driver)
            ->get(route('trips.map-data', $this->trip));

        $jsonAfter = $responseAfter->json();
        $this->assertEquals($this->stop2->id, $jsonAfter['active_stop_id']);
        $this->assertCount(2, $jsonAfter['stops']);
    }

    /**
     * Test 13: UI Views render sukses tanpa error Blade.
     */
    public function test_pbi10_ui_views_render_successfully(): void
    {
        $this->stop1->update([
            'status' => TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
            'arrived_at' => Carbon::now(),
        ]);

        // 1. PIC Receipts Index
        $resIndex = $this->actingAs($this->picSchoolA)
            ->get(route('school.receipts.index'));
        $resIndex->assertOk();
        $resIndex->assertSee('Daftar Penerimaan Makanan');
        $resIndex->assertSee($this->schoolA->name);

        // 2. PIC Receipt Show (Form Inspection)
        $resShow = $this->actingAs($this->picSchoolA)
            ->get(route('school.receipts.show', $this->stop1));
        $resShow->assertOk();
        $resShow->assertSee('Penghitungan Porsi Fisik');
        $resShow->assertSee('Uji Organoleptik Kedatangan');

        // 3. Driver Handover View (Page D04)
        $resHandover = $this->actingAs($this->driver)
            ->get(route('driver.trips.stops.handover', [$this->trip, $this->stop1]));
        $resHandover->assertOk();
        $resHandover->assertSee('Bukti Foto Serah Terima');
        $resHandover->assertSee($this->schoolA->name);
    }
}
