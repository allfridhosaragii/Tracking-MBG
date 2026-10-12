<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\Sppg;
use App\Models\User;
use Database\Seeders\MbgTrackBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MbgTrackBaselineSeeder::class);
    }

    public function test_unauthenticated_user_cannot_access_protected_dashboards(): void
    {
        $this->get('/head/dashboard')->assertRedirect('/login');
        $this->get('/nutrition/dashboard')->assertRedirect('/login');
        $this->get('/production/dashboard')->assertRedirect('/login');
        $this->get('/driver/dashboard')->assertRedirect('/login');
        $this->get('/school/dashboard')->assertRedirect('/login');
    }

    public function test_driver_cannot_access_head_dashboard(): void
    {
        $driver = User::where('email', 'kurir@mbgtrack.test')->first();

        $response = $this->actingAs($driver)->get('/head/dashboard');

        $response->assertStatus(403);
    }

    public function test_pic_sekolah_cannot_access_nutrition_dashboard(): void
    {
        $pic = User::where('email', 'pic.sdn01@mbgtrack.test')->first();

        $response = $this->actingAs($pic)->get('/nutrition/dashboard');

        $response->assertStatus(403);
    }

    public function test_asisten_lapangan_cannot_access_driver_dashboard(): void
    {
        $asisten = User::where('email', 'asisten@mbgtrack.test')->first();

        $response = $this->actingAs($asisten)->get('/driver/dashboard');

        $response->assertStatus(403);
    }

    public function test_school_pic_can_view_own_assigned_school(): void
    {
        $picSdn01 = User::where('email', 'pic.sdn01@mbgtrack.test')->first();
        $schoolA = School::where('school_code', 'SCH-SDN-01')->first();

        $response = $this->actingAs($picSdn01)->get(route('schools.show', $schoolA->id));

        $response->assertStatus(200);
        $response->assertSee($schoolA->name);
    }

    public function test_school_pic_is_forbidden_from_viewing_other_school(): void
    {
        $picSdn01 = User::where('email', 'pic.sdn01@mbgtrack.test')->first();
        $schoolB = School::where('school_code', 'SCH-SMPN-02')->first();

        // PIC SDN 01 attempts to view SMPN 02 data
        $response = $this->actingAs($picSdn01)->get(route('schools.show', $schoolB->id));

        $response->assertStatus(403);
    }

    public function test_kepala_sppg_can_view_schools_within_own_sppg(): void
    {
        $kepala = User::where('email', 'kepala@mbgtrack.test')->first();
        $schoolA = School::where('school_code', 'SCH-SDN-01')->first();

        $response = $this->actingAs($kepala)->get(route('schools.show', $schoolA->id));

        $response->assertStatus(200);
        $response->assertSee($schoolA->name);
    }

    public function test_deactivated_user_in_session_is_terminated_by_active_middleware(): void
    {
        $user = User::where('email', 'asisten@mbgtrack.test')->first();
        
        // Deactivate user in database
        $user->update(['is_active' => false]);

        $response = $this->actingAs($user)->get('/production/dashboard');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
