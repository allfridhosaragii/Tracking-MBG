<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Sppg;
use App\Models\User;
use Database\Seeders\MbgTrackBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MbgTrackBaselineSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/head/employees');
        $response->assertRedirect('/login');

        $responseCreate = $this->get('/head/employees/create');
        $responseCreate->assertRedirect('/login');
    }

    public function test_non_head_roles_are_forbidden(): void
    {
        $driver = User::where('email', 'kurir@mbgtrack.test')->first();
        $pic = User::where('email', 'pic.sdn01@mbgtrack.test')->first();
        $nutritionist = User::where('email', 'gizi@mbgtrack.test')->first();
        $fieldAssistant = User::where('email', 'asisten@mbgtrack.test')->first();

        foreach ([$driver, $pic, $nutritionist, $fieldAssistant] as $user) {
            $response = $this->actingAs($user)->get('/head/employees');
            $response->assertStatus(403);

            $responseCreate = $this->actingAs($user)->get('/head/employees/create');
            $responseCreate->assertStatus(403);

            $responsePost = $this->actingAs($user)->post('/head/employees', [
                'name' => 'Pegawai Palsu',
                'email' => 'palsu@test.id',
                'role' => UserRole::KURIR->value,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);
            $responsePost->assertStatus(403);
        }
    }

    public function test_head_can_view_employees_index(): void
    {
        $head = User::where('email', 'kepala@mbgtrack.test')->first();

        $response = $this->actingAs($head)->get('/head/employees');

        $response->assertStatus(200);
        $response->assertSee('Direktori Pegawai');
        $response->assertSee('Ratna Sari'); // Nutritionist
        $response->assertSee('Ahmad Fauzi');  // Field assistant
        $response->assertSee('Dedi Pratama'); // Courier
    }

    public function test_tenant_isolation_head_cannot_see_employees_from_other_sppg(): void
    {
        $head1 = User::where('email', 'kepala@mbgtrack.test')->first();

        // Create SPPG 2 and employee for SPPG 2
        $sppg2 = Sppg::create([
            'code' => 'SPPG-TEST-02',
            'name' => 'SPPG Wilayah Dua',
            'address' => 'Jl. Wilayah Dua No. 2',
            'is_active' => true,
        ]);

        $employeeSppg2 = User::create([
            'sppg_id' => $sppg2->id,
            'name' => 'Pegawai SPPG Dua',
            'email' => 'pegawai2@sppg2.test',
            'password' => Hash::make('password123'),
            'role' => UserRole::PENGAWAS_GIZI->value,
            'is_active' => true,
        ]);

        $response = $this->actingAs($head1)->get('/head/employees');
        $response->assertStatus(200);
        $response->assertDontSee('Pegawai SPPG Dua');
        $response->assertDontSee('pegawai2@sppg2.test');

        // Head 1 cannot view employee 2 detail
        $responseDetail = $this->actingAs($head1)->get("/head/employees/{$employeeSppg2->id}");
        $responseDetail->assertStatus(403);

        // Head 1 cannot edit employee 2
        $responseEdit = $this->actingAs($head1)->get("/head/employees/{$employeeSppg2->id}/edit");
        $responseEdit->assertStatus(403);

        // Head 1 cannot update employee 2
        $responseUpdate = $this->actingAs($head1)->put("/head/employees/{$employeeSppg2->id}", [
            'name' => 'Hacked Name',
            'email' => 'pegawai2@sppg2.test',
            'role' => UserRole::PENGAWAS_GIZI->value,
        ]);
        $responseUpdate->assertStatus(403);

        // Head 1 cannot toggle active status of employee 2
        $responseToggle = $this->actingAs($head1)->patch("/head/employees/{$employeeSppg2->id}/toggle-active");
        $responseToggle->assertStatus(403);

        // Head 1 cannot reset password of employee 2
        $responseReset = $this->actingAs($head1)->post("/head/employees/{$employeeSppg2->id}/reset-password", [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $responseReset->assertStatus(403);
    }

    public function test_head_can_search_and_filter_employees(): void
    {
        $head = User::where('email', 'kepala@mbgtrack.test')->first();

        // Search by query
        $responseSearch = $this->actingAs($head)->get('/head/employees?q=Dedi');
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('Dedi Pratama');
        $responseSearch->assertDontSee('Ratna Sari');

        // Filter by role
        $responseRole = $this->actingAs($head)->get('/head/employees?role=PENGAWAS_GIZI');
        $responseRole->assertStatus(200);
        $responseRole->assertSee('Ratna Sari');
        $responseRole->assertDontSee('Dedi Pratama');

        // Filter by inactive status (seeded with Eko Nonaktif)
        $responseInactive = $this->actingAs($head)->get('/head/employees?status=inactive');
        $responseInactive->assertStatus(200);
        $responseInactive->assertSee('Eko Nonaktif');
        $responseInactive->assertDontSee('Ratna Sari');
    }

    public function test_head_can_create_new_employee_and_audit_is_recorded(): void
    {
        $head = User::where('email', 'kepala@mbgtrack.test')->first();

        $response = $this->actingAs($head)->post('/head/employees', [
            'name' => 'Siti Nurhaliza, S.Tr.Gz',
            'email' => 'siti.gizi@mbgtrack.test',
            'phone' => '081234567899',
            'role' => UserRole::PENGAWAS_GIZI->value,
            'password' => 'passwordSecure123',
            'password_confirmation' => 'passwordSecure123',
        ]);

        $response->assertRedirect('/head/employees');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'sppg_id' => $head->sppg_id,
            'email' => 'siti.gizi@mbgtrack.test',
            'name' => 'Siti Nurhaliza, S.Tr.Gz',
            'role' => UserRole::PENGAWAS_GIZI->value,
            'is_active' => true,
        ]);

        $newEmployee = User::where('email', 'siti.gizi@mbgtrack.test')->first();
        $this->assertNotNull($newEmployee);

        // Verify ActivityLog was recorded
        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $head->id,
            'auditable_type' => User::class,
            'auditable_id' => $newEmployee->id,
            'event' => 'created',
        ]);

        // Clear head session before logging in as newly created employee
        $this->post('/logout');

        // Verify the new employee can log in
        $responseLogin = $this->post('/login', [
            'email' => 'siti.gizi@mbgtrack.test',
            'password' => 'passwordSecure123',
        ]);
        $responseLogin->assertRedirect('/nutrition/dashboard');
    }

    public function test_create_employee_validates_required_fields_and_unique_email(): void
    {
        $head = User::where('email', 'kepala@mbgtrack.test')->first();

        // Empty data
        $response = $this->actingAs($head)->post('/head/employees', []);
        $response->assertSessionHasErrors(['name', 'email', 'role', 'password']);

        // Duplicate email
        $responseDuplicate = $this->actingAs($head)->post('/head/employees', [
            'name' => 'Duplicate Email',
            'email' => 'gizi@mbgtrack.test', // already taken
            'role' => UserRole::PENGAWAS_GIZI->value,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $responseDuplicate->assertSessionHasErrors(['email']);

        // Invalid role
        $responseInvalidRole = $this->actingAs($head)->post('/head/employees', [
            'name' => 'Invalid Role',
            'email' => 'newuser@mbgtrack.test',
            'role' => 'SUPER_ADMIN_INVALID',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $responseInvalidRole->assertSessionHasErrors(['role']);

        // Password confirmation mismatch
        $responsePasswordMismatch = $this->actingAs($head)->post('/head/employees', [
            'name' => 'Mismatch Password',
            'email' => 'newuser@mbgtrack.test',
            'role' => UserRole::KURIR->value,
            'password' => 'password123',
            'password_confirmation' => 'mismatch123',
        ]);
        $responsePasswordMismatch->assertSessionHasErrors(['password']);
    }

    public function test_head_can_view_employee_details_and_audit_history(): void
    {
        $head = User::where('email', 'kepala@mbgtrack.test')->first();
        $employee = User::where('email', 'gizi@mbgtrack.test')->first();

        // Record a mock activity log
        ActivityLog::record(
            event: 'role_changed',
            auditable: $employee,
            actor: $head,
            oldValues: ['role' => 'ASISTEN_LAPANGAN'],
            newValues: ['role' => 'PENGAWAS_GIZI'],
            metadata: ['action' => 'Promosi penugasan'],
        );

        $response = $this->actingAs($head)->get("/head/employees/{$employee->id}");

        $response->assertStatus(200);
        $response->assertSee($employee->name);
        $response->assertSee('Cakupan Tugas');
        $response->assertSee('Histori Jejak Audit');
        $response->assertSee('Perubahan Role / Hak Akses');
    }

    public function test_head_can_update_employee_details_and_role(): void
    {
        $head = User::where('email', 'kepala@mbgtrack.test')->first();
        $driver = User::where('email', 'kurir@mbgtrack.test')->first();

        $response = $this->actingAs($head)->put("/head/employees/{$driver->id}", [
            'name' => 'Doni Kuriawan, A.Md',
            'email' => 'doni.new@mbgtrack.test',
            'phone' => '081299999999',
            'role' => UserRole::ASISTEN_LAPANGAN->value,
            'is_active' => true,
        ]);

        $response->assertRedirect("/head/employees/{$driver->id}");
        $response->assertSessionHas('status');

        $driver->refresh();
        $this->assertEquals('Doni Kuriawan, A.Md', $driver->name);
        $this->assertEquals('doni.new@mbgtrack.test', $driver->email);
        $this->assertEquals(UserRole::ASISTEN_LAPANGAN, $driver->role);

        // Verify audit log for role change
        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $head->id,
            'auditable_id' => $driver->id,
            'event' => 'role_changed',
        ]);
    }

    public function test_head_cannot_demote_or_change_own_role(): void
    {
        $head = User::where('email', 'kepala@mbgtrack.test')->first();

        $response = $this->actingAs($head)->put("/head/employees/{$head->id}", [
            'name' => 'Dr. Hendra Wibowo',
            'email' => 'kepala@mbgtrack.test',
            'role' => UserRole::KURIR->value, // Self-demotion attempt
        ]);

        $response->assertSessionHasErrors(['role']);

        $head->refresh();
        $this->assertEquals(UserRole::KEPALA_SPPG, $head->role);
    }

    public function test_head_can_toggle_employee_active_status_and_inactive_user_cannot_login(): void
    {
        $head = User::where('email', 'kepala@mbgtrack.test')->first();
        $assistant = User::where('email', 'asisten@mbgtrack.test')->first();

        $this->assertTrue($assistant->is_active);

        // Deactivate
        $response = $this->actingAs($head)->patch("/head/employees/{$assistant->id}/toggle-active");
        $response->assertRedirect();
        $assistant->refresh();
        $this->assertFalse($assistant->is_active);

        // Verify audit
        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $head->id,
            'auditable_id' => $assistant->id,
            'event' => 'status_toggled',
        ]);

        // Attempt login as deactivated user -> must be blocked
        $this->post('/logout');
        $responseLogin = $this->post('/login', [
            'email' => 'asisten@mbgtrack.test',
            'password' => 'password',
        ]);
        $responseLogin->assertSessionHasErrors(['email']);
        $this->assertGuest();

        // Reactivate
        $responseReactivate = $this->actingAs($head)->patch("/head/employees/{$assistant->id}/toggle-active");
        $responseReactivate->assertRedirect();
        $assistant->refresh();
        $this->assertTrue($assistant->is_active);

        // Login succeeds again
        $this->post('/logout');
        $responseLoginAgain = $this->post('/login', [
            'email' => 'asisten@mbgtrack.test',
            'password' => 'password',
        ]);
        $responseLoginAgain->assertRedirect('/production/dashboard');
    }

    public function test_sole_active_head_cannot_deactivate_self(): void
    {
        $head = User::where('email', 'kepala@mbgtrack.test')->first();

        $response = $this->actingAs($head)->patch("/head/employees/{$head->id}/toggle-active");
        $response->assertSessionHasErrors(['is_active']);

        $head->refresh();
        $this->assertTrue($head->is_active);
    }

    public function test_head_can_reset_employee_password(): void
    {
        $head = User::where('email', 'kepala@mbgtrack.test')->first();
        $driver = User::where('email', 'kurir@mbgtrack.test')->first();

        $response = $this->actingAs($head)->post("/head/employees/{$driver->id}/reset-password", [
            'password' => 'brandNewPassword99',
            'password_confirmation' => 'brandNewPassword99',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        // Verify audit log
        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $head->id,
            'auditable_id' => $driver->id,
            'event' => 'password_reset',
        ]);

        $this->post('/logout');

        // Old password fails
        $responseOldLogin = $this->post('/login', [
            'email' => 'kurir@mbgtrack.test',
            'password' => 'password',
        ]);
        $responseOldLogin->assertSessionHasErrors(['email']);

        // New password succeeds
        $responseNewLogin = $this->post('/login', [
            'email' => 'kurir@mbgtrack.test',
            'password' => 'brandNewPassword99',
        ]);
        $responseNewLogin->assertRedirect('/driver/dashboard');
    }
}
