<?php

namespace Tests\Feature;

use App\Enums\MenuStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Sppg;
use App\Models\User;
use Database\Seeders\MbgTrackBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $nutritionist;
    protected User $head;
    protected User $assistant;
    protected User $driver;
    protected Sppg $sppg;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MbgTrackBaselineSeeder::class);

        $this->sppg = Sppg::first();
        $this->nutritionist = User::where('email', 'gizi@mbgtrack.test')->first();
        $this->head = User::where('email', 'kepala@mbgtrack.test')->first();
        $this->assistant = User::where('email', 'asisten@mbgtrack.test')->first();
        $this->driver = User::where('email', 'kurir@mbgtrack.test')->first();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/nutrition/menus');
        $response->assertRedirect('/login');

        $responseCreate = $this->get('/nutrition/menus/create');
        $responseCreate->assertRedirect('/login');
    }

    public function test_unauthorized_roles_cannot_access_nutrition_workspace(): void
    {
        foreach ([$this->assistant, $this->driver] as $user) {
            $response = $this->actingAs($user)->get('/nutrition/menus');
            $response->assertStatus(403);

            $responsePost = $this->actingAs($user)->post('/nutrition/menus', [
                'menu_code' => 'MN-TEST-01',
                'name' => 'Menu Uji Coba',
            ]);
            $responsePost->assertStatus(403);
        }
    }

    public function test_pengawas_gizi_can_view_menu_list(): void
    {
        $response = $this->actingAs($this->nutritionist)->get('/nutrition/menus');
        $response->assertStatus(200);
        $response->assertSee('Katalog Racikan Menu &amp; Kandungan Gizi', false);
    }

    public function test_pengawas_gizi_can_create_a_menu_draft(): void
    {
        $response = $this->actingAs($this->nutritionist)->post('/nutrition/menus', [
            'menu_code' => 'MN-AYAM-01',
            'name' => 'Paket Ayam Semur & Sayur Bayam',
            'effective_from' => '2026-10-15',
        ]);

        $response->assertRedirect();

        $menu = Menu::where('menu_code', 'MN-AYAM-01')->first();
        $this->assertNotNull($menu);
        $this->assertEquals('Paket Ayam Semur & Sayur Bayam', $menu->name);
        $this->assertEquals(1, $menu->version);
        $this->assertEquals(MenuStatus::DRAFT, $menu->status);
        $this->assertEquals($this->sppg->id, $menu->sppg_id);
        $this->assertEquals($this->nutritionist->id, $menu->created_by);

        // Verify audit log
        $this->assertDatabaseHas('activity_logs', [
            'event' => 'menu.draft_created',
            'auditable_type' => Menu::class,
            'auditable_id' => $menu->id,
            'actor_id' => $this->nutritionist->id,
        ]);
    }

    public function test_draft_menu_code_validation_rejects_special_characters(): void
    {
        $response = $this->actingAs($this->nutritionist)->post('/nutrition/menus', [
            'menu_code' => 'MN AYAM @!#',
            'name' => 'Menu Tidak Valid',
        ]);

        $response->assertSessionHasErrors('menu_code');
        $this->assertEquals(0, Menu::count());
    }

    public function test_pengawas_gizi_can_update_draft_menu(): void
    {
        $menu = Menu::create([
            'sppg_id' => $this->sppg->id,
            'menu_code' => 'MN-IKAN-01',
            'name' => 'Paket Ikan Nila Bakar Awal',
            'version' => 1,
            'status' => MenuStatus::DRAFT,
            'created_by' => $this->nutritionist->id,
        ]);

        $response = $this->actingAs($this->nutritionist)->put("/nutrition/menus/{$menu->id}", [
            'name' => 'Paket Ikan Nila Bakar Madu & Lalap Terstandar',
            'effective_from' => '2026-10-20',
        ]);

        $response->assertRedirect("/nutrition/menus/{$menu->id}");
        $menu->refresh();
        $this->assertEquals('Paket Ikan Nila Bakar Madu & Lalap Terstandar', $menu->name);

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'menu.draft_updated',
            'auditable_id' => $menu->id,
        ]);
    }

    public function test_pengawas_gizi_can_add_component_item_to_draft(): void
    {
        $menu = Menu::create([
            'sppg_id' => $this->sppg->id,
            'menu_code' => 'MN-AYAM-01',
            'name' => 'Paket Ayam Semur',
            'version' => 1,
            'status' => MenuStatus::DRAFT,
            'created_by' => $this->nutritionist->id,
        ]);

        $response = $this->actingAs($this->nutritionist)->post("/nutrition/menus/{$menu->id}/items", [
            'item_name' => 'Nasi Putih Pulen',
            'serving_weight_g' => 150.00,
            'energy_kcal' => 195.00,
            'protein_g' => 3.60,
            'carbohydrate_g' => 43.50,
            'fat_g' => 0.40,
            'fiber_g' => 0.30,
            'nutrition_source' => 'TKPI Kemenkes 2019 Kode 010',
        ]);

        $response->assertRedirect("/nutrition/menus/{$menu->id}");

        $this->assertDatabaseHas('menu_items', [
            'menu_id' => $menu->id,
            'item_name' => 'Nasi Putih Pulen',
            'serving_weight_g' => 150.00,
            'energy_kcal' => 195.00,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'menu_item.added',
            'auditable_id' => $menu->id,
        ]);
    }

    public function test_adding_component_with_invalid_weight_or_negative_nutrients_is_rejected(): void
    {
        $menu = Menu::create([
            'sppg_id' => $this->sppg->id,
            'menu_code' => 'MN-AYAM-01',
            'name' => 'Paket Ayam Semur',
            'version' => 1,
            'status' => MenuStatus::DRAFT,
            'created_by' => $this->nutritionist->id,
        ]);

        // Zero or negative weight
        $response1 = $this->actingAs($this->nutritionist)->post("/nutrition/menus/{$menu->id}/items", [
            'item_name' => 'Nasi Putih',
            'serving_weight_g' => 0,
        ]);
        $response1->assertSessionHasErrors('serving_weight_g');

        // Negative nutrient
        $response2 = $this->actingAs($this->nutritionist)->post("/nutrition/menus/{$menu->id}/items", [
            'item_name' => 'Nasi Putih',
            'serving_weight_g' => 100,
            'protein_g' => -5,
        ]);
        $response2->assertSessionHasErrors('protein_g');

        $this->assertEquals(0, MenuItem::count());
    }

    public function test_nutrition_totals_calculated_accurately(): void
    {
        $menu = Menu::create([
            'sppg_id' => $this->sppg->id,
            'menu_code' => 'MN-LENGKAP-01',
            'name' => 'Paket Menu Komplit',
            'version' => 1,
            'status' => MenuStatus::DRAFT,
            'created_by' => $this->nutritionist->id,
        ]);

        // Item 1: Nasi
        $menu->items()->create([
            'item_name' => 'Nasi Putih',
            'serving_weight_g' => 150.00,
            'energy_kcal' => 195.00,
            'protein_g' => 3.60,
            'carbohydrate_g' => 43.50,
            'fat_g' => 0.40,
            'fiber_g' => 0.30,
            'nutrition_source' => 'TKPI 2019',
        ]);

        // Item 2: Ayam Panggang
        $menu->items()->create([
            'item_name' => 'Ayam Panggang',
            'serving_weight_g' => 80.00,
            'energy_kcal' => 210.00,
            'protein_g' => 24.50,
            'carbohydrate_g' => 0.00,
            'fat_g' => 12.20,
            'fiber_g' => 0.00,
            'nutrition_source' => 'TKPI 2019',
        ]);

        // Item 3: Tumis Buncis
        $menu->items()->create([
            'item_name' => 'Tumis Buncis Jagung',
            'serving_weight_g' => 70.00,
            'energy_kcal' => 65.50,
            'protein_g' => 2.10,
            'carbohydrate_g' => 8.40,
            'fat_g' => 3.10,
            'fiber_g' => 2.50,
            'nutrition_source' => 'TKPI 2019',
        ]);

        $menu->refresh();

        $this->assertEquals(300.00, $menu->totalServingWeight());
        $this->assertEquals(470.50, $menu->totalEnergy());
        $this->assertEquals(30.20, $menu->totalProtein());
        $this->assertEquals(51.90, $menu->totalCarbohydrates());
        $this->assertEquals(15.70, $menu->totalFat());
        $this->assertEquals(2.80, $menu->totalFiber());
        $this->assertTrue($menu->isComplete());
    }

    public function test_activation_blocked_if_menu_has_no_components(): void
    {
        $menu = Menu::create([
            'sppg_id' => $this->sppg->id,
            'menu_code' => 'MN-KOSONG-01',
            'name' => 'Menu Tanpa Bahan',
            'version' => 1,
            'status' => MenuStatus::DRAFT,
            'created_by' => $this->nutritionist->id,
        ]);

        $response = $this->actingAs($this->nutritionist)->patch("/nutrition/menus/{$menu->id}/activate");
        $response->assertSessionHas('error');

        $menu->refresh();
        $this->assertEquals(MenuStatus::DRAFT, $menu->status);
    }

    public function test_activation_blocked_if_nutrients_are_incomplete_or_source_missing(): void
    {
        $menu = Menu::create([
            'sppg_id' => $this->sppg->id,
            'menu_code' => 'MN-PARSIAL-01',
            'name' => 'Menu Parsial',
            'version' => 1,
            'status' => MenuStatus::DRAFT,
            'created_by' => $this->nutritionist->id,
        ]);

        // Component has null protein and missing source
        $menu->items()->create([
            'item_name' => 'Sup Sayur',
            'serving_weight_g' => 100.00,
            'energy_kcal' => 50.00,
            'protein_g' => null, // INCOMPLETE
            'carbohydrate_g' => 10.00,
            'fat_g' => 1.00,
            'fiber_g' => 2.00,
            'nutrition_source' => '', // EMPTY
        ]);

        $response = $this->actingAs($this->nutritionist)->patch("/nutrition/menus/{$menu->id}/activate");
        $response->assertSessionHas('error');

        $menu->refresh();
        $this->assertEquals(MenuStatus::DRAFT, $menu->status);
        $this->assertFalse($menu->isComplete());
    }

    public function test_valid_complete_draft_menu_can_be_activated(): void
    {
        $menu = Menu::create([
            'sppg_id' => $this->sppg->id,
            'menu_code' => 'MN-VALID-01',
            'name' => 'Menu Valid Siap Sah',
            'version' => 1,
            'status' => MenuStatus::DRAFT,
            'created_by' => $this->nutritionist->id,
        ]);

        $menu->items()->create([
            'item_name' => 'Nasi Pulen',
            'serving_weight_g' => 150.00,
            'energy_kcal' => 195.00,
            'protein_g' => 3.60,
            'carbohydrate_g' => 43.50,
            'fat_g' => 0.40,
            'fiber_g' => 0.30,
            'nutrition_source' => 'TKPI 2019',
        ]);

        $response = $this->actingAs($this->nutritionist)->patch("/nutrition/menus/{$menu->id}/activate");
        $response->assertRedirect("/nutrition/menus/{$menu->id}");
        $response->assertSessionHas('status');

        $menu->refresh();
        $this->assertEquals(MenuStatus::ACTIVE, $menu->status);
        $this->assertEquals($this->nutritionist->id, $menu->approved_by);
        $this->assertNotNull($menu->approved_at);

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'menu.activated',
            'auditable_id' => $menu->id,
            'actor_id' => $this->nutritionist->id,
        ]);
    }

    public function test_activating_new_version_archives_previous_active_version_with_same_code(): void
    {
        // Version 1 is currently active
        $v1 = Menu::create([
            'sppg_id' => $this->sppg->id,
            'menu_code' => 'MN-SIKLUS-01',
            'name' => 'Resep Siklus v1',
            'version' => 1,
            'status' => MenuStatus::ACTIVE,
            'created_by' => $this->nutritionist->id,
            'approved_by' => $this->nutritionist->id,
            'approved_at' => now()->subDays(5),
        ]);

        $v1->items()->create([
            'item_name' => 'Nasi Merah',
            'serving_weight_g' => 150.00,
            'energy_kcal' => 180.00,
            'protein_g' => 4.00,
            'carbohydrate_g' => 38.00,
            'fat_g' => 1.00,
            'fiber_g' => 3.00,
            'nutrition_source' => 'TKPI 2019',
        ]);

        // Version 2 is complete draft
        $v2 = Menu::create([
            'sppg_id' => $this->sppg->id,
            'menu_code' => 'MN-SIKLUS-01',
            'name' => 'Resep Siklus v2 Revisi',
            'version' => 2,
            'status' => MenuStatus::DRAFT,
            'created_by' => $this->nutritionist->id,
        ]);

        $v2->items()->create([
            'item_name' => 'Nasi Merah',
            'serving_weight_g' => 160.00,
            'energy_kcal' => 192.00,
            'protein_g' => 4.20,
            'carbohydrate_g' => 40.00,
            'fat_g' => 1.10,
            'fiber_g' => 3.20,
            'nutrition_source' => 'TKPI 2019',
        ]);

        $response = $this->actingAs($this->nutritionist)->patch("/nutrition/menus/{$v2->id}/activate");
        $response->assertRedirect("/nutrition/menus/{$v2->id}");

        $v1->refresh();
        $v2->refresh();

        $this->assertEquals(MenuStatus::ARCHIVED, $v1->status, 'Versi 1 harus bertransisi ke ARCHIVED');
        $this->assertEquals(MenuStatus::ACTIVE, $v2->status, 'Versi 2 harus menjadi ACTIVE');

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'menu.archived_for_new_active',
            'auditable_id' => $v1->id,
        ]);
    }

    public function test_active_menu_is_immutable_cannot_be_updated_directly(): void
    {
        $activeMenu = Menu::create([
            'sppg_id' => $this->sppg->id,
            'menu_code' => 'MN-KUNCI-01',
            'name' => 'Menu Sah Terkunci',
            'version' => 1,
            'status' => MenuStatus::ACTIVE,
            'created_by' => $this->nutritionist->id,
            'approved_by' => $this->nutritionist->id,
            'approved_at' => now(),
        ]);

        // Attempt direct update should fail policy or validation
        $response = $this->actingAs($this->nutritionist)->put("/nutrition/menus/{$activeMenu->id}", [
            'name' => 'Nama Diretas',
        ]);

        $response->assertStatus(403);

        $activeMenu->refresh();
        $this->assertEquals('Menu Sah Terkunci', $activeMenu->name);
    }

    public function test_cannot_add_or_delete_items_on_active_menu(): void
    {
        $activeMenu = Menu::create([
            'sppg_id' => $this->sppg->id,
            'menu_code' => 'MN-KUNCI-02',
            'name' => 'Menu Sah Terkunci 2',
            'version' => 1,
            'status' => MenuStatus::ACTIVE,
            'created_by' => $this->nutritionist->id,
        ]);

        $item = $activeMenu->items()->create([
            'item_name' => 'Komponen Awal',
            'serving_weight_g' => 100,
            'energy_kcal' => 100,
            'protein_g' => 5,
            'carbohydrate_g' => 15,
            'fat_g' => 2,
            'fiber_g' => 1,
            'nutrition_source' => 'TKPI',
        ]);

        // Try adding item to active menu
        $responseAdd = $this->actingAs($this->nutritionist)->post("/nutrition/menus/{$activeMenu->id}/items", [
            'item_name' => 'Komponen Tambahan',
            'serving_weight_g' => 50,
        ]);
        $responseAdd->assertStatus(403);

        // Try deleting item from active menu
        $responseDelete = $this->actingAs($this->nutritionist)->delete("/nutrition/menus/{$activeMenu->id}/items/{$item->id}");
        $responseDelete->assertStatus(403);

        $this->assertDatabaseHas('menu_items', ['id' => $item->id]);
    }

    public function test_branching_new_version_creates_draft_v_plus_one_with_cloned_items(): void
    {
        $v1 = Menu::create([
            'sppg_id' => $this->sppg->id,
            'menu_code' => 'MN-CABANG-01',
            'name' => 'Resep Original',
            'version' => 1,
            'status' => MenuStatus::ACTIVE,
            'created_by' => $this->nutritionist->id,
            'approved_by' => $this->nutritionist->id,
            'approved_at' => now(),
        ]);

        $v1->items()->create([
            'item_name' => 'Ikan Mas Bakar',
            'serving_weight_g' => 100.00,
            'energy_kcal' => 150.00,
            'protein_g' => 20.00,
            'carbohydrate_g' => 0.00,
            'fat_g' => 6.00,
            'fiber_g' => 0.00,
            'nutrition_source' => 'TKPI 2019',
        ]);

        $response = $this->actingAs($this->nutritionist)->post("/nutrition/menus/{$v1->id}/new-version");
        $response->assertRedirect();

        $v2 = Menu::where('menu_code', 'MN-CABANG-01')->where('version', 2)->first();
        $this->assertNotNull($v2);
        $this->assertEquals(MenuStatus::DRAFT, $v2->status);
        $this->assertEquals('Resep Original', $v2->name);
        $this->assertNull($v2->approved_by);
        $this->assertNull($v2->approved_at);

        // Verify cloned items
        $this->assertCount(1, $v2->items);
        $this->assertEquals('Ikan Mas Bakar', $v2->items->first()->item_name);

        // Verify original v1 is completely untouched
        $v1->refresh();
        $this->assertEquals(MenuStatus::ACTIVE, $v1->status);
        $this->assertEquals(1, $v1->version);

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'menu.version_branched',
            'auditable_id' => $v2->id,
        ]);
    }

    public function test_cross_tenant_isolation_sppg_b_cannot_view_or_modify_sppg_a_menu(): void
    {
        // Create second SPPG and nutritionist
        $sppgB = Sppg::create([
            'code' => 'SPPG-MLG-02',
            'name' => 'SPPG Malang Kota',
            'address' => 'Jl. Ijen No. 10, Malang',
            'max_daily_portions' => 1000,
            'is_active' => true,
        ]);

        $nutritionistB = User::create([
            'sppg_id' => $sppgB->id,
            'name' => 'Gizi SPPG B',
            'email' => 'gizi.b@mbgtrack.test',
            'password' => 'password123',
            'role' => UserRole::PENGAWAS_GIZI,
            'is_active' => true,
        ]);

        // Menu belongs to SPPG A
        $menuA = Menu::create([
            'sppg_id' => $this->sppg->id,
            'menu_code' => 'MN-TENANT-A',
            'name' => 'Menu Rahasia SPPG A',
            'version' => 1,
            'status' => MenuStatus::DRAFT,
            'created_by' => $this->nutritionist->id,
        ]);

        // Nutritionist B cannot view Menu A
        $responseView = $this->actingAs($nutritionistB)->get("/nutrition/menus/{$menuA->id}");
        $responseView->assertStatus(403);

        // Nutritionist B cannot update Menu A
        $responseUpdate = $this->actingAs($nutritionistB)->put("/nutrition/menus/{$menuA->id}", [
            'name' => 'Hack dari Tenant B',
        ]);
        $responseUpdate->assertStatus(403);

        // Nutritionist B cannot activate Menu A
        $responseActivate = $this->actingAs($nutritionistB)->patch("/nutrition/menus/{$menuA->id}/activate");
        $responseActivate->assertStatus(403);
    }
}
