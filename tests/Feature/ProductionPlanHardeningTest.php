<?php

namespace Tests\Feature;

use App\Enums\MenuStatus;
use App\Enums\PortionRequestStatus;
use App\Enums\ProductionPlanStatus;
use App\Models\DailyPortionRequest;
use App\Models\Menu;
use App\Models\ProductionAllocation;
use App\Models\ProductionPlan;
use App\Models\School;
use App\Models\Sppg;
use App\Models\User;
use App\Services\PortionRequestService;
use App\Services\ProductionPlanService;
use Carbon\Carbon;
use Database\Seeders\MbgTrackBaselineSeeder;
use Database\Seeders\MenuBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductionPlanHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $headSppg;
    protected User $picSchoolA;
    protected School $schoolA;
    protected School $schoolB;
    protected School $schoolC;
    protected Sppg $sppg;
    protected Menu $activeMenu;
    protected ProductionPlanService $planService;
    protected PortionRequestService $portionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MbgTrackBaselineSeeder::class);
        $this->seed(MenuBaselineSeeder::class);

        $this->sppg = Sppg::where('code', 'SPPG-SUB-01')->firstOrFail();
        $this->schoolA = School::where('school_code', 'SCH-SDN-01')->firstOrFail();
        $this->schoolB = School::where('school_code', 'SCH-SMPN-02')->firstOrFail();
        $this->schoolC = School::where('school_code', 'SCH-SMAN-05')->firstOrFail();

        $this->headSppg = User::where('email', 'kepala@mbgtrack.test')->firstOrFail();
        $this->picSchoolA = User::where('email', 'pic.sdn01@mbgtrack.test')->firstOrFail();

        $this->planService = app(ProductionPlanService::class);
        $this->portionService = app(PortionRequestService::class);

        $this->activeMenu = Menu::where('sppg_id', $this->sppg->id)
            ->where('status', MenuStatus::ACTIVE)
            ->firstOrFail();
    }

    /**
     * HARD GATE A: Finalisasi ditolak bila satu sekolah eligible sengaja dihilangkan dari payload.
     * Skenario prompt:
     * Sekolah A: 340, CONFIRMED.
     * Sekolah B: 276, CONFIRMED.
     * Sekolah C: 410, CONFIRMED.
     * Payload hanya mengirim Sekolah A.
     */
    public function test_finalization_rejected_when_an_eligible_school_is_deliberately_omitted_from_payload(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(12)->format('Y-m-d');

        $reqA = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
        ]);

        $reqB = DailyPortionRequest::create([
            'school_id' => $this->schoolB->id,
            'service_date' => $targetDate,
            'registered_count' => 276,
            'requested_portions' => 276,
            'status' => PortionRequestStatus::CONFIRMED,
        ]);

        $reqC = DailyPortionRequest::create([
            'school_id' => $this->schoolC->id,
            'service_date' => $targetDate,
            'registered_count' => 410,
            'requested_portions' => 410,
            'status' => PortionRequestStatus::CONFIRMED,
        ]);

        $plan = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->activeMenu->id,
            'service_date' => $targetDate,
            'status' => ProductionPlanStatus::PLANNED,
            'created_by' => $this->headSppg->id,
        ]);

        // Payload sengaja hanya mengirim Sekolah A (menghilangkan B dan C)
        $response = $this->actingAs($this->headSppg)->post(route('head.production.plans.finalize', $plan), [
            'allocations' => [
                ['school_id' => $this->schoolA->id, 'portion_request_id' => $reqA->id, 'allocated_portions' => 340],
            ],
        ]);

        $response->assertSessionHasErrors('allocations');
        $this->assertEquals(ProductionPlanStatus::PLANNED, $plan->fresh()->status);
    }

    /**
     * HARD GATE A: Sekolah yang masih pending/draf memblokir finalisasi.
     */
    public function test_finalization_rejected_when_a_school_is_still_pending_without_request(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(13)->format('Y-m-d');

        $reqA = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
        ]);

        // Sekolah B dan C belum dibuat permintaan (pending)

        $plan = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->activeMenu->id,
            'service_date' => $targetDate,
            'status' => ProductionPlanStatus::PLANNED,
            'created_by' => $this->headSppg->id,
        ]);

        $response = $this->actingAs($this->headSppg)->post(route('head.production.plans.finalize', $plan), [
            'allocations' => [
                ['school_id' => $this->schoolA->id, 'portion_request_id' => $reqA->id, 'allocated_portions' => 340],
            ],
        ]);

        $response->assertSessionHasErrors('allocations');
    }

    /**
     * HARD GATE B: Under-allocation ditolak (alokasi < kebutuhan resmi).
     */
    public function test_finalization_rejected_when_allocated_portions_less_than_official_request(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(14)->format('Y-m-d');

        $reqA = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
        ]);

        DailyPortionRequest::create([
            'school_id' => $this->schoolB->id,
            'service_date' => $targetDate,
            'registered_count' => 276,
            'requested_portions' => 0,
            'status' => PortionRequestStatus::CANCELLED,
            'revision_reason' => 'Libur resmi',
        ]);

        DailyPortionRequest::create([
            'school_id' => $this->schoolC->id,
            'service_date' => $targetDate,
            'registered_count' => 410,
            'requested_portions' => 0,
            'status' => PortionRequestStatus::CANCELLED,
            'revision_reason' => 'Kegiatan studi',
        ]);

        $plan = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->activeMenu->id,
            'service_date' => $targetDate,
            'status' => ProductionPlanStatus::PLANNED,
            'created_by' => $this->headSppg->id,
        ]);

        // Manipulasi: Sekolah A minta 340, tapi dikirim 200
        $response = $this->actingAs($this->headSppg)->post(route('head.production.plans.finalize', $plan), [
            'allocations' => [
                ['school_id' => $this->schoolA->id, 'portion_request_id' => $reqA->id, 'allocated_portions' => 200],
            ],
        ]);

        $response->assertSessionHasErrors('allocations.0.allocated_portions');
    }

    /**
     * HARD GATE B: Over-allocation ditolak (alokasi > kebutuhan resmi).
     */
    public function test_finalization_rejected_when_allocated_portions_greater_than_official_request(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(15)->format('Y-m-d');

        $reqA = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
        ]);

        DailyPortionRequest::create([
            'school_id' => $this->schoolB->id,
            'service_date' => $targetDate,
            'registered_count' => 276,
            'requested_portions' => 0,
            'status' => PortionRequestStatus::CANCELLED,
            'revision_reason' => 'Libur',
        ]);

        DailyPortionRequest::create([
            'school_id' => $this->schoolC->id,
            'service_date' => $targetDate,
            'registered_count' => 410,
            'requested_portions' => 0,
            'status' => PortionRequestStatus::CANCELLED,
            'revision_reason' => 'Libur',
        ]);

        $plan = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->activeMenu->id,
            'service_date' => $targetDate,
            'status' => ProductionPlanStatus::PLANNED,
            'created_by' => $this->headSppg->id,
        ]);

        // Manipulasi: Sekolah A minta 340, tapi dikirim 500
        $response = $this->actingAs($this->headSppg)->post(route('head.production.plans.finalize', $plan), [
            'allocations' => [
                ['school_id' => $this->schoolA->id, 'portion_request_id' => $reqA->id, 'allocated_portions' => 500],
            ],
        ]);

        $response->assertSessionHasErrors('allocations.0.allocated_portions');
    }

    /**
     * HARD GATE C: Portion request tidak dapat dialokasikan ulang pada plan lain di tanggal yang sama.
     */
    public function test_portion_request_cannot_be_allocated_across_multiple_plans_on_same_date(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(16)->format('Y-m-d');

        $reqA = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
        ]);

        DailyPortionRequest::create([
            'school_id' => $this->schoolB->id,
            'service_date' => $targetDate,
            'registered_count' => 276,
            'requested_portions' => 0,
            'status' => PortionRequestStatus::CANCELLED,
            'revision_reason' => 'Libur',
        ]);

        DailyPortionRequest::create([
            'school_id' => $this->schoolC->id,
            'service_date' => $targetDate,
            'registered_count' => 410,
            'requested_portions' => 0,
            'status' => PortionRequestStatus::CANCELLED,
            'revision_reason' => 'Libur',
        ]);

        // Plan 1 difinalisasi
        $plan1 = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->activeMenu->id,
            'service_date' => $targetDate,
            'status' => ProductionPlanStatus::PLANNED,
            'created_by' => $this->headSppg->id,
        ]);

        $this->actingAs($this->headSppg)->post(route('head.production.plans.finalize', $plan1), [
            'allocations' => [
                ['school_id' => $this->schoolA->id, 'portion_request_id' => $reqA->id, 'allocated_portions' => 340],
            ],
        ])->assertRedirect();

        $this->assertEquals(ProductionPlanStatus::FINALIZED, $plan1->fresh()->status);

        // Buat menu kedua untuk membuat plan kedua
        $menu2 = Menu::create([
            'sppg_id' => $this->sppg->id,
            'menu_code' => 'MENU-DIET-02',
            'version' => 1,
            'name' => 'Menu Khusus Alergi',
            'status' => MenuStatus::ACTIVE,
            'created_by' => $this->headSppg->id,
        ]);

        $plan2 = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $menu2->id,
            'service_date' => $targetDate,
            'status' => ProductionPlanStatus::PLANNED,
            'created_by' => $this->headSppg->id,
        ]);

        // Coba alokasikan reqA lagi ke plan2
        $response = $this->actingAs($this->headSppg)->post(route('head.production.plans.finalize', $plan2), [
            'allocations' => [
                ['school_id' => $this->schoolA->id, 'portion_request_id' => $reqA->id, 'allocated_portions' => 340],
            ],
        ]);

        $response->assertSessionHasErrors('allocations');
    }

    /**
     * HARD GATE D: PIC tidak dapat mengajukan revisi jika request sudah digunakan dalam plan FINALIZED.
     */
    public function test_cannot_submit_revision_when_request_is_used_in_finalized_plan(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(17)->format('Y-m-d');

        $reqA = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
        ]);

        DailyPortionRequest::create([
            'school_id' => $this->schoolB->id,
            'service_date' => $targetDate,
            'registered_count' => 276,
            'requested_portions' => 0,
            'status' => PortionRequestStatus::CANCELLED,
            'revision_reason' => 'Libur',
        ]);

        DailyPortionRequest::create([
            'school_id' => $this->schoolC->id,
            'service_date' => $targetDate,
            'registered_count' => 410,
            'requested_portions' => 0,
            'status' => PortionRequestStatus::CANCELLED,
            'revision_reason' => 'Libur',
        ]);

        $plan = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->activeMenu->id,
            'service_date' => $targetDate,
            'status' => ProductionPlanStatus::PLANNED,
            'created_by' => $this->headSppg->id,
        ]);

        $this->actingAs($this->headSppg)->post(route('head.production.plans.finalize', $plan), [
            'allocations' => [
                ['school_id' => $this->schoolA->id, 'portion_request_id' => $reqA->id, 'allocated_portions' => 340],
            ],
        ])->assertRedirect();

        // Coba ajukan revisi untuk reqA yang sudah difinalisasi
        $this->expectException(ValidationException::class);
        $this->portionService->submitRevision($reqA->fresh(), [
            'requested_portions' => 200,
            'revision_reason' => 'Pengurangan mendadak',
        ], $this->picSchoolA);
    }

    /**
     * HARD GATE E: Kapasitas dapur 0 atau null ditolak saat finalisasi.
     */
    public function test_finalization_rejected_when_kitchen_capacity_is_zero(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(18)->format('Y-m-d');

        // Set kapasitas SPPG = 0
        $this->sppg->update(['max_daily_portions' => 0]);

        $reqA = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
        ]);

        DailyPortionRequest::create([
            'school_id' => $this->schoolB->id,
            'service_date' => $targetDate,
            'registered_count' => 276,
            'requested_portions' => 0,
            'status' => PortionRequestStatus::CANCELLED,
            'revision_reason' => 'Libur',
        ]);

        DailyPortionRequest::create([
            'school_id' => $this->schoolC->id,
            'service_date' => $targetDate,
            'registered_count' => 410,
            'requested_portions' => 0,
            'status' => PortionRequestStatus::CANCELLED,
            'revision_reason' => 'Libur',
        ]);

        $plan = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->activeMenu->id,
            'service_date' => $targetDate,
            'status' => ProductionPlanStatus::PLANNED,
            'created_by' => $this->headSppg->id,
        ]);

        $response = $this->actingAs($this->headSppg)->post(route('head.production.plans.finalize', $plan), [
            'allocations' => [
                ['school_id' => $this->schoolA->id, 'portion_request_id' => $reqA->id, 'allocated_portions' => 340],
            ],
        ]);

        $response->assertSessionHasErrors('capacity');
    }

    /**
     * HARD GATE F: Agregasi memisahkan unapproved exception dari porsi resmi eligible.
     */
    public function test_aggregate_strictly_segregates_unapproved_exceptions_from_eligible_portions(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(19)->format('Y-m-d');

        // Sekolah A: 340 porsi (normal, eligible)
        DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
        ]);

        // Sekolah B: 350 porsi (melebihi 276 siswa, belum disetujui -> unapproved exception)
        DailyPortionRequest::create([
            'school_id' => $this->schoolB->id,
            'service_date' => $targetDate,
            'registered_count' => 276,
            'requested_portions' => 350,
            'status' => PortionRequestStatus::CONFIRMED,
            'revision_reason' => 'Tambahan porsi kegiatan',
            'is_exceeds_approved' => false,
        ]);

        $agg = $this->portionService->getSppgAggregateForDate($this->sppg->id, $targetDate);

        // Porsi eligible resmi HANYA Sekolah A (340 porsi), TIDAK termasuk 350 dari Sekolah B!
        $this->assertEquals(340, $agg['total_eligible_portions']);
        $this->assertEquals(340, $agg['total_confirmed_portions']);
        $this->assertEquals(350, $agg['total_exception_pending_portions']);
        $this->assertEquals(690, $agg['total_administrative_portions']);
        $this->assertEquals(1, $agg['exception_count']);
        $this->assertFalse($agg['all_confirmed']);
    }

    /**
     * HARD GATE D: Kepala SPPG tidak dapat menyetujui revisi jika porsi sudah terikat pada plan FINALIZED.
     */
    public function test_cannot_approve_revision_when_request_is_used_in_finalized_plan(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(20)->format('Y-m-d');

        $reqA = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
        ]);

        DailyPortionRequest::create([
            'school_id' => $this->schoolB->id,
            'service_date' => $targetDate,
            'registered_count' => 276,
            'requested_portions' => 0,
            'status' => PortionRequestStatus::CANCELLED,
            'revision_reason' => 'Libur',
        ]);

        DailyPortionRequest::create([
            'school_id' => $this->schoolC->id,
            'service_date' => $targetDate,
            'registered_count' => 410,
            'requested_portions' => 0,
            'status' => PortionRequestStatus::CANCELLED,
            'revision_reason' => 'Libur',
        ]);

        $plan = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->activeMenu->id,
            'service_date' => $targetDate,
            'status' => ProductionPlanStatus::PLANNED,
            'created_by' => $this->headSppg->id,
        ]);

        $this->actingAs($this->headSppg)->post(route('head.production.plans.finalize', $plan), [
            'allocations' => [
                ['school_id' => $this->schoolA->id, 'portion_request_id' => $reqA->id, 'allocated_portions' => 340],
            ],
        ])->assertRedirect();

        // Paksa simulasikan record memiliki revised_portions (misal via data anomali)
        $reqA->update(['status' => PortionRequestStatus::REVISED, 'revised_portions' => 200]);

        $this->expectException(ValidationException::class);
        $this->portionService->approveRevision($reqA->fresh(), $this->headSppg);
    }

    /**
     * HARD GATE D: Pengecualian kuota siswa tidak dapat diubah jika sudah difinalisasi dalam rencana produksi.
     */
    public function test_cannot_modify_exceeding_exception_when_request_is_used_in_finalized_plan(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(21)->format('Y-m-d');

        $reqA = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 300,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
            'revision_reason' => 'Porsi ekstra kegiatan resmi',
            'is_exceeds_approved' => true,
        ]);

        DailyPortionRequest::create([
            'school_id' => $this->schoolB->id,
            'service_date' => $targetDate,
            'registered_count' => 276,
            'requested_portions' => 0,
            'status' => PortionRequestStatus::CANCELLED,
            'revision_reason' => 'Libur',
        ]);

        DailyPortionRequest::create([
            'school_id' => $this->schoolC->id,
            'service_date' => $targetDate,
            'registered_count' => 410,
            'requested_portions' => 0,
            'status' => PortionRequestStatus::CANCELLED,
            'revision_reason' => 'Libur',
        ]);

        $plan = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->activeMenu->id,
            'service_date' => $targetDate,
            'status' => ProductionPlanStatus::PLANNED,
            'created_by' => $this->headSppg->id,
        ]);

        $this->actingAs($this->headSppg)->post(route('head.production.plans.finalize', $plan), [
            'allocations' => [
                ['school_id' => $this->schoolA->id, 'portion_request_id' => $reqA->id, 'allocated_portions' => 340],
            ],
        ])->assertRedirect();

        $this->expectException(ValidationException::class);
        $this->portionService->rejectExceedingException($reqA->fresh(), 'Tolak pasca finalisasi', $this->headSppg);
    }

    /**
     * HARD GATE C: Total alokasi lintas beberapa rencana produksi tidak boleh melampaui kapasitas dapur.
     */
    public function test_cumulative_allocation_across_multiple_plans_cannot_exceed_kitchen_capacity(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(22)->format('Y-m-d');

        // Kapasitas SPPG = 500
        $this->sppg->update(['max_daily_portions' => 500]);

        $reqA = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
        ]);

        $reqB = DailyPortionRequest::create([
            'school_id' => $this->schoolB->id,
            'service_date' => $targetDate,
            'registered_count' => 276,
            'requested_portions' => 276,
            'status' => PortionRequestStatus::CONFIRMED,
        ]);

        DailyPortionRequest::create([
            'school_id' => $this->schoolC->id,
            'service_date' => $targetDate,
            'registered_count' => 410,
            'requested_portions' => 0,
            'status' => PortionRequestStatus::CANCELLED,
            'revision_reason' => 'Libur',
        ]);

        // Plan 1 untuk Sekolah A (340 porsi)
        $plan1 = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->activeMenu->id,
            'service_date' => $targetDate,
            'status' => ProductionPlanStatus::PLANNED,
            'created_by' => $this->headSppg->id,
        ]);

        // Finalisasi Plan 1 (340 porsi <= 500 porsi)
        // Di sini Sekolah B di-bypass dari payload plan 1 tapi sudah ada request sah
        // Namun Hard Gate A mengecek bahwa di Plan 1, apakah seluruh sekolah aktif harus dialokasikan?
        // Jika satu plan hanya melayani sebagian sekolah karena multi-menu:
        // Cek validasi kapasitas:
        $menu2 = Menu::create([
            'sppg_id' => $this->sppg->id,
            'menu_code' => 'MENU-DIET-03',
            'version' => 1,
            'name' => 'Menu Khusus Balita',
            'status' => MenuStatus::ACTIVE,
            'created_by' => $this->headSppg->id,
        ]);

        $plan2 = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $menu2->id,
            'service_date' => $targetDate,
            'status' => ProductionPlanStatus::PLANNED,
            'created_by' => $this->headSppg->id,
        ]);

        // Simulasi Plan 1 sudah difinalisasi dengan alokasi Sekolah A (340 porsi)
        $plan1->update(['status' => ProductionPlanStatus::FINALIZED, 'finalized_at' => now(), 'finalized_by' => $this->headSppg->id]);
        ProductionAllocation::create([
            'production_plan_id' => $plan1->id,
            'portion_request_id' => $reqA->id,
            'school_id' => $this->schoolA->id,
            'allocated_portions' => 340,
        ]);

        // Coba finalisasi Plan 2 dengan Sekolah B (276 porsi)
        // 340 + 276 = 616 > 500 porsi (kapasitas dapur)
        $this->expectException(ValidationException::class);
        $this->planService->finalizePlan($plan2, [
            ['school_id' => $this->schoolB->id, 'portion_request_id' => $reqB->id, 'allocated_portions' => 276],
        ], $this->headSppg);
    }
}

