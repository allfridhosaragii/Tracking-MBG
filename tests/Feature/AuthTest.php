<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\Sppg;
use App\Models\User;
use Database\Seeders\MbgTrackBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MbgTrackBaselineSeeder::class);
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Portal MBG-Track');
        $response->assertSee('LINGKUNGAN PENGUJIAN');
    }

    public function test_root_redirects_to_login_for_guests(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_kepala_sppg_login_redirects_to_head_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'kepala@mbgtrack.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('head.dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'kepala@mbgtrack.test')->first();
        $this->assertNotNull($user->last_login_at);
    }

    public function test_pengawas_gizi_login_redirects_to_nutrition_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'gizi@mbgtrack.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('nutrition.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_asisten_lapangan_login_redirects_to_production_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'asisten@mbgtrack.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('production.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_kurir_login_redirects_to_driver_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'kurir@mbgtrack.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('driver.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_pic_sekolah_login_redirects_to_school_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'pic.sdn01@mbgtrack.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('school.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_inactive_user_is_prevented_from_logging_in(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'inactive@mbgtrack.test',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_invalid_password_is_rejected(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'kepala@mbgtrack.test',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::where('email', 'kepala@mbgtrack.test')->first();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_demo_presets_and_default_credentials_are_hidden_in_production(): void
    {
        config(['app.env' => 'production']);

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertDontSee('Preset Akun Simulasi');
        $response->assertDontSee('fillPreset');
        $response->assertDontSee('value="kepala@mbgtrack.test"', false);
        $response->assertDontSee('value="password"', false);
    }
}
