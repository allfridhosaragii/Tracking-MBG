<?php

namespace Tests\Feature;

use App\Enums\MenuStatus;
use App\Enums\PortionRequestStatus;
use App\Enums\ProductionPlanStatus;
use App\Models\ActivityLog;
use App\Models\DailyPortionRequest;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\ProductionAllocation;
use App\Models\ProductionPlan;
use App\Models\School;
use App\Models\Sppg;
use App\Models\User;
use App\Services\PortionRequestService;
use App\Services\ProductionPlanService;
use Carbon\Carbon;
use Database\Seeders\MbgTrackBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductionPlanTest extends TestCase
{
    use RefreshDatabase;

    protected User $headSppg;
    protected User $asisten;
    protected User $gizi;
    protected User $kurir;
    protected User $picSchoolA;
    protected School $schoolA;
    protected School $schoolB;
    protected School $schoolC;
    protected Sppg $sppg;
    protected Menu $activeMenu;
    protected ProductionPlanService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MbgTrackBaselineSeeder::class);
        $this->seed(\Database\Seeders\MenuBaselineSeeder::class);

        $this->sppg = Sppg::where('code', 'SPPG-SUB-01')->firstOrFail();
        $this->schoolA = School::where('school_code', 'SCH-SDN-01')->firstOrFail();
        $this->schoolB = School::where('school_code', 'SCH-SMPN-02')->firstOrFail();
        $this->schoolC = School::where('school_code', 'SCH-SMAN-05')->firstOrFail();

        $this->headSppg = User::where('email', 'kepala@mbgtrack.test')->firstOrFail();
        $this->asisten = User::where('email', 'asisten@mbgtrack.test')->firstOrFail();
        $this->gizi = User::where('email', 'gizi@mbgtrack.test')->firstOrFail();
        $this->kurir = User::where('email', 'kurir@mbgtrack.test')->firstOrFail();
        $this->picSchoolA = User::where('email', 'pic.sdn01@mbgtrack.test')->firstOrFail();

        $this->service = app(ProductionPlanService::class);

        // Ensure active menu exists for this SPPG
        $this->activeMenu = Menu::where('sppg_id', $this->sppg->id)
            ->where('status', MenuStatus::ACTIVE)
            ->firstOrFail();
    }

    /**
     * 1. Kepala SPPG dapat membuka workspace perencanaan produksi (Page K04).
     */
    public function test_head_sppg_can_view_production_planning_workspace(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(1)->format('Y-m-d');

        $response = $this->actingAs($this->headSppg)->get(route('head.production.index', ['date' => $targetDate]));

        $response->assertOk();
        $response->assertSee('Perencanaan Produksi', false);
        $response->assertSee($this->sppg->name);
        $response->assertSee($this->schoolA->name);
        $response->assertSee($this->schoolB->name);
        $response->assertSee($this->schoolC->name);
    }

    /**
     * 2. Role tidak sah (Kurir, Gizi, Asisten, PIC) ditolak mengakses workspace perencanaan.
     */
    public function test_unauthorized_roles_cannot_access_head_production_planning(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->format('Y-m-d');

        foreach ([$this->asisten, $this->gizi, $this->kurir, $this->picSchoolA] as $unauthorized) {
            $response = $this->actingAs($unauthorized)->get(route('head.production.index', ['date' => $targetDate]));
            $response->assertStatus(403);
        }
    }

    /**
     * 3. Kepala SPPG dapat membuat rencana produksi awal (PLANNED).
     */
    public function test_head_sppg_can_create_initial_production_plan(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(2)->format('Y-m-d');

        $response = $this->actingAs($this->headSppg)->post(route('head.production.plans.store'), [
            'service_date' => $targetDate,
            'menu_id' => $this->activeMenu->id,
            'notes' => 'Rencana awal hari Rabu',
        ]);

        $response->assertRedirect(route('head.production.index', [
            'date' => $targetDate,
            'menu_id' => $this->activeMenu->id,
        ]));

        $plan = ProductionPlan::where('sppg_id', $this->sppg->id)
            ->whereDate('service_date', $targetDate)
            ->first();

        $this->assertNotNull($plan);
        $this->assertEquals(ProductionPlanStatus::PLANNED, $plan->status);
        $this->assertEquals($this->activeMenu->id, $plan->menu_id);
        $this->assertEquals($this->headSppg->id, $plan->created_by);

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'production_plan.created',
            'auditable_type' => ProductionPlan::class,
            'auditable_id' => $plan->id,
            'actor_id' => $this->headSppg->id,
        ]);
    }

    /**
     * 4. Hanya menu ACTIVE yang sah boleh digunakan; Menu DRAFT atau ARCHIVED ditolak.
     */
    public function test_draft_or_archived_menu_is_rejected_for_production_plan(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(3)->format('Y-m-d');

        // Create draft menu
        $draftMenu = Menu::create([
            'sppg_id' => $this->sppg->id,
            'menu_code' => 'MNU-DRAFT-01',
            'name' => 'Menu Uji Coba Belum Sah',
            'version' => 1,
            'status' => MenuStatus::DRAFT,
            'created_by' => $this->gizi->id,
        ]);

        $response = $this->actingAs($this->headSppg)->post(route('head.production.plans.store'), [
            'service_date' => $targetDate,
            'menu_id' => $draftMenu->id,
        ]);

        $response->assertSessionHasErrors('menu_id');
        $this->assertDatabaseMissing('production_plans', [
            'sppg_id' => $this->sppg->id,
            'menu_id' => $draftMenu->id,
        ]);
    }

    /**
     * 5. Isolasi Tenant: Menu dari SPPG lain ditolak.
     */
    public function test_cross_tenant_menu_is_rejected(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(4)->format('Y-m-d');

        $sppgOther = Sppg::create([
            'code' => 'SPPG-MDN-99',
            'name' => 'SPPG Medan',
            'address' => 'Jl. Medan',
            'latitude' => 3.59,
            'longitude' => 98.67,
            'max_daily_portions' => 1000,
            'is_active' => true,
        ]);

        $otherMenu = Menu::create([
            'sppg_id' => $sppgOther->id,
            'menu_code' => 'MNU-MDN-01',
            'name' => 'Menu Khusus Medan',
            'version' => 1,
            'status' => MenuStatus::ACTIVE,
            'created_by' => $this->gizi->id,
        ]);

        $response = $this->actingAs($this->headSppg)->post(route('head.production.plans.store'), [
            'service_date' => $targetDate,
            'menu_id' => $otherMenu->id,
        ]);

        $response->assertSessionHasErrors('menu_id');
    }

    /**
     * 6. Skenario Penuh Prompt: 340 + 276 + 410 = 1.026 porsi jika ketiganya sah.
     * Mengunci seluruh request ke LOCKED dan menyimpan alokasi secara atomik.
     */
    public function test_happy_path_finalization_locks_requests_and_creates_allocations(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(5)->format('Y-m-d');

        // Create confirmed requests for all 3 schools: 340 + 276 + 410 = 1026
        $reqA = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'present_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
            'confirmed_by' => $this->picSchoolA->id,
            'confirmed_at' => now(),
        ]);

        $reqB = DailyPortionRequest::create([
            'school_id' => $this->schoolB->id,
            'service_date' => $targetDate,
            'registered_count' => 276,
            'present_count' => 276,
            'requested_portions' => 276,
            'status' => PortionRequestStatus::CONFIRMED,
            'confirmed_by' => $this->picSchoolA->id,
            'confirmed_at' => now(),
        ]);

        $reqC = DailyPortionRequest::create([
            'school_id' => $this->schoolC->id,
            'service_date' => $targetDate,
            'registered_count' => 410,
            'present_count' => 410,
            'requested_portions' => 410,
            'status' => PortionRequestStatus::CONFIRMED,
            'confirmed_by' => $this->picSchoolA->id,
            'confirmed_at' => now(),
        ]);

        // Create initial plan
        $plan = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->activeMenu->id,
            'service_date' => $targetDate,
            'status' => ProductionPlanStatus::PLANNED,
            'created_by' => $this->headSppg->id,
        ]);

        // Finalize plan
        $response = $this->actingAs($this->headSppg)->post(route('head.production.plans.finalize', $plan), [
            'allocations' => [
                ['school_id' => $this->schoolA->id, 'portion_request_id' => $reqA->id, 'allocated_portions' => 340],
                ['school_id' => $this->schoolB->id, 'portion_request_id' => $reqB->id, 'allocated_portions' => 276],
                ['school_id' => $this->schoolC->id, 'portion_request_id' => $reqC->id, 'allocated_portions' => 410],
            ],
        ]);

        $response->assertRedirect(route('head.production.index', [
            'date' => $targetDate,
            'menu_id' => $this->activeMenu->id,
        ]));

        $plan->refresh();
        $this->assertEquals(ProductionPlanStatus::FINALIZED, $plan->status);
        $this->assertEquals($this->headSppg->id, $plan->finalized_by);
        $this->assertNotNull($plan->finalized_at);
        $this->assertEquals(1026, $plan->totalAllocatedPortions());

        // Invariant: All 3 requests must transition to LOCKED with locked_at
        $reqA->refresh();
        $reqB->refresh();
        $reqC->refresh();

        $this->assertEquals(PortionRequestStatus::LOCKED, $reqA->status);
        $this->assertEquals(PortionRequestStatus::LOCKED, $reqB->status);
        $this->assertEquals(PortionRequestStatus::LOCKED, $reqC->status);
        $this->assertNotNull($reqA->locked_at);
        $this->assertNotNull($reqB->locked_at);
        $this->assertNotNull($reqC->locked_at);

        // Allocations created
        $this->assertDatabaseHas('production_allocations', [
            'production_plan_id' => $plan->id,
            'school_id' => $this->schoolA->id,
            'allocated_portions' => 340,
        ]);
        $this->assertDatabaseHas('production_allocations', [
            'production_plan_id' => $plan->id,
            'school_id' => $this->schoolB->id,
            'allocated_portions' => 276,
        ]);
        $this->assertDatabaseHas('production_allocations', [
            'production_plan_id' => $plan->id,
            'school_id' => $this->schoolC->id,
            'allocated_portions' => 410,
        ]);

        // Audit Log
        $this->assertDatabaseHas('activity_logs', [
            'event' => 'production_plan.finalized',
            'auditable_type' => ProductionPlan::class,
            'auditable_id' => $plan->id,
            'actor_id' => $this->headSppg->id,
        ]);
    }

    /**
     * 7. Jika salah satu sekolah pending, status menunjukkan kondisi parsial dan finalisasi diblokir jika data tidak lengkap.
     */
    public function test_pending_school_blocks_finalization_of_invalid_request(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(6)->format('Y-m-d');

        // School A confirmed: 340
        $reqA = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::CONFIRMED,
            'confirmed_by' => $this->picSchoolA->id,
            'confirmed_at' => now(),
        ]);

        // School B is still in DRAFT (pending!)
        $reqB = DailyPortionRequest::create([
            'school_id' => $this->schoolB->id,
            'service_date' => $targetDate,
            'registered_count' => 276,
            'status' => PortionRequestStatus::DRAFT,
        ]);

        $plan = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->activeMenu->id,
            'service_date' => $targetDate,
            'status' => ProductionPlanStatus::PLANNED,
            'created_by' => $this->headSppg->id,
        ]);

        // Attempting to finalize allocating draft/pending school B
        $response = $this->actingAs($this->headSppg)->post(route('head.production.plans.finalize', $plan), [
            'allocations' => [
                ['school_id' => $this->schoolA->id, 'portion_request_id' => $reqA->id, 'allocated_portions' => 340],
                ['school_id' => $this->schoolB->id, 'portion_request_id' => $reqB->id, 'allocated_portions' => 276],
            ],
        ]);

        $response->assertSessionHasErrors();
        $plan->refresh();
        $this->assertEquals(ProductionPlanStatus::PLANNED, $plan->status);
    }

    /**
     * 8. Revisi yang belum disetujui ditolak untuk alokasi produksi.
     */
    public function test_unapproved_revision_blocks_finalization(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(7)->format('Y-m-d');

        $reqA = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $targetDate,
            'registered_count' => 340,
            'requested_portions' => 340,
            'revised_portions' => 320,
            'status' => PortionRequestStatus::REVISED,
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
                ['school_id' => $this->schoolA->id, 'portion_request_id' => $reqA->id, 'allocated_portions' => 320],
            ],
        ]);

        $response->assertSessionHasErrors();
        $plan->refresh();
        $this->assertEquals(ProductionPlanStatus::PLANNED, $plan->status);
    }

    /**
     * 9. Alokasi melebihi kapasitas dapur SPPG diblokir.
     */
    public function test_allocations_exceeding_kitchen_capacity_are_blocked(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(8)->format('Y-m-d');

        // Set SPPG capacity strictly to 500
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
            'revision_reason' => 'Libur semester',
        ]);

        // 340 + 276 = 616 > 500!
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
                ['school_id' => $this->schoolB->id, 'portion_request_id' => $reqB->id, 'allocated_portions' => 276],
            ],
        ]);

        $response->assertSessionHasErrors('capacity');
        $this->assertEquals(ProductionPlanStatus::PLANNED, $plan->fresh()->status);
    }

    /**
     * 10. Duplikasi alokasi sekolah yang sama dalam satu plan ditolak.
     */
    public function test_duplicate_school_allocation_in_same_plan_is_rejected(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(9)->format('Y-m-d');

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
            'revision_reason' => 'Studi lapangan',
        ]);

        DailyPortionRequest::create([
            'school_id' => $this->schoolC->id,
            'service_date' => $targetDate,
            'registered_count' => 410,
            'requested_portions' => 0,
            'status' => PortionRequestStatus::CANCELLED,
            'revision_reason' => 'Renovasi',
        ]);

        $plan = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->activeMenu->id,
            'service_date' => $targetDate,
            'status' => ProductionPlanStatus::PLANNED,
            'created_by' => $this->headSppg->id,
        ]);

        // Duplicate schoolA in allocations array
        $response = $this->actingAs($this->headSppg)->post(route('head.production.plans.finalize', $plan), [
            'allocations' => [
                ['school_id' => $this->schoolA->id, 'portion_request_id' => $reqA->id, 'allocated_portions' => 200],
                ['school_id' => $this->schoolA->id, 'portion_request_id' => $reqA->id, 'allocated_portions' => 140],
            ],
        ]);

        $response->assertSessionHasErrors();
        $this->assertEquals(0, ProductionAllocation::where('production_plan_id', $plan->id)->count());
    }

    /**
     * 11. Finalisasi ganda bersifat idempoten dan tidak menduplikasi allocations atau activity log.
     */
    public function test_double_submit_finalize_is_idempotent(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(10)->format('Y-m-d');

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
            'revision_reason' => 'Kegiatan luar sekolah',
        ]);

        DailyPortionRequest::create([
            'school_id' => $this->schoolC->id,
            'service_date' => $targetDate,
            'registered_count' => 410,
            'requested_portions' => 0,
            'status' => PortionRequestStatus::CANCELLED,
            'revision_reason' => 'Renovasi dapur',
        ]);

        $plan = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->activeMenu->id,
            'service_date' => $targetDate,
            'status' => ProductionPlanStatus::PLANNED,
            'created_by' => $this->headSppg->id,
        ]);

        $payload = [
            'allocations' => [
                ['school_id' => $this->schoolA->id, 'portion_request_id' => $reqA->id, 'allocated_portions' => 340],
            ],
        ];

        // First submit
        $this->actingAs($this->headSppg)->post(route('head.production.plans.finalize', $plan), $payload)->assertRedirect();

        $logCountFirst = ActivityLog::where('event', 'production_plan.finalized')->where('auditable_id', $plan->id)->count();
        $allocCountFirst = ProductionAllocation::where('production_plan_id', $plan->id)->count();

        $this->assertEquals(1, $logCountFirst);
        $this->assertEquals(1, $allocCountFirst);

        // Second submit (same payload)
        $this->actingAs($this->headSppg)->post(route('head.production.plans.finalize', $plan), $payload)->assertRedirect();

        $logCountSecond = ActivityLog::where('event', 'production_plan.finalized')->where('auditable_id', $plan->id)->count();
        $allocCountSecond = ProductionAllocation::where('production_plan_id', $plan->id)->count();

        $this->assertEquals(1, $logCountSecond, 'Duplicate finalize must not spam activity logs');
        $this->assertEquals(1, $allocCountSecond, 'Duplicate finalize must not duplicate allocation rows');
    }

    /**
     * 12. Asisten Lapangan dapat melihat rencana FINALIZED secara read-only.
     */
    public function test_assistant_can_view_finalized_plan_read_only(): void
    {
        $today = Carbon::today('Asia/Jakarta')->format('Y-m-d');

        $reqA = DailyPortionRequest::create([
            'school_id' => $this->schoolA->id,
            'service_date' => $today,
            'registered_count' => 340,
            'requested_portions' => 340,
            'status' => PortionRequestStatus::LOCKED,
            'locked_at' => now(),
        ]);

        $plan = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->activeMenu->id,
            'service_date' => $today,
            'status' => ProductionPlanStatus::FINALIZED,
            'finalized_by' => $this->headSppg->id,
            'finalized_at' => now(),
            'created_by' => $this->headSppg->id,
        ]);

        ProductionAllocation::create([
            'production_plan_id' => $plan->id,
            'portion_request_id' => $reqA->id,
            'school_id' => $this->schoolA->id,
            'allocated_portions' => 340,
        ]);

        $response = $this->actingAs($this->asisten)->get(route('production.dashboard', ['date' => $today]));

        $response->assertOk();
        $response->assertSee('Target Resmi Difinalisasi');
        $response->assertSee($this->activeMenu->name);
        $response->assertSee('340');
        $response->assertSee($this->schoolA->name);
    }

    /**
     * 13. Asisten Lapangan TIDAK DAPAT memfinalisasi rencana produksi (403 Forbidden).
     */
    public function test_assistant_cannot_finalize_production_plan(): void
    {
        $targetDate = Carbon::tomorrow('Asia/Jakarta')->addDays(11)->format('Y-m-d');

        $plan = ProductionPlan::create([
            'sppg_id' => $this->sppg->id,
            'menu_id' => $this->activeMenu->id,
            'service_date' => $targetDate,
            'status' => ProductionPlanStatus::PLANNED,
            'created_by' => $this->headSppg->id,
        ]);

        $response = $this->actingAs($this->asisten)->post(route('head.production.plans.finalize', $plan), [
            'allocations' => [],
        ]);

        $response->assertStatus(403);
    }
}
