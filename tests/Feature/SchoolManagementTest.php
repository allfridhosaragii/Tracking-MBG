<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\Sppg;
use App\Models\User;
use Database\Seeders\MbgTrackBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MbgTrackBaselineSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/head/schools');
        $response->assertRedirect('/login');

        $responseCreate = $this->get('/head/schools/create');
        $responseCreate->assertRedirect('/login');
    }

    public function test_non_head_roles_are_forbidden(): void
    {
        $driver = User::where('email', 'kurir@mbgtrack.test')->first();
        $pic = User::where('email', 'pic.sdn01@mbgtrack.test')->first();
        $nutritionist = User::where('email', 'gizi@mbgtrack.test')->first();
        $fieldAssistant = User::where('email', 'asisten@mbgtrack.test')->first();

        foreach ([$driver, $pic, $nutritionist, $fieldAssistant] as $user) {
            $response = $this->actingAs($user)->get('/head/schools');
            $response->assertStatus(403);

            $responsePost = $this->actingAs($user)->post('/head/schools', [
                'school_code' => 'TEST999',
                'name' => 'Test Sekolah',
                'education_level' => 'SD',
                'address' => 'Jl. Test',
            ]);
            $responsePost->assertStatus(403);
        }
    }

    public function test_inactive_head_is_logged_out_and_redirected(): void
    {
        $head = User::where('email', 'kepala@mbgtrack.test')->first();
        $head->update(['is_active' => false]);

        $response = $this->actingAs($head)->get('/head/schools');
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }

    public function test_head_can_view_schools_index(): void
    {
        $head = User::where('email', 'kepala@mbgtrack.test')->first();

        $response = $this->actingAs($head)->get('/head/schools');

        $response->assertStatus(200);
        $response->assertSee('Manajemen Sekolah');
        $response->assertSee('SDN Percobaan 01 Surabaya');
        $response->assertSee('SMPN 02 Harapan Bangsa');
        $response->assertSee('SMAN 05 Teladan Surabaya');
    }

    public function test_head_can_search_and_filter_schools(): void
    {
        $head = User::where('email', 'kepala@mbgtrack.test')->first();

        // Search by query
        $responseSearch = $this->actingAs($head)->get('/head/schools?q=SMPN');
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('SMPN 02 Harapan Bangsa');
        $responseSearch->assertDontSee('SMAN 05 Teladan Surabaya');

        // Filter by inactive status (currently 0 inactive)
        $responseInactive = $this->actingAs($head)->get('/head/schools?status=inactive');
        $responseInactive->assertStatus(200);
        $responseInactive->assertSee('Tidak ada sekolah ditemukan');
    }

    public function test_head_can_view_create_school_form(): void
    {
        $head = User::where('email', 'kepala@mbgtrack.test')->first();

        $response = $this->actingAs($head)->get('/head/schools/create');

        $response->assertStatus(200);
        $response->assertSee('Registrasi Sekolah Binaan Baru');
        $response->assertSee('Kode Sekolah / NPSN');
        $response->assertSee('Jumlah Siswa Penerima Terdaftar');
    }

    public function test_head_can_store_new_school_with_valid_data(): void
    {
        $head = User::where('email', 'kepala@mbgtrack.test')->first();

        // Buat kandidat user untuk PIC
        $candidate = User::factory()->create([
            'sppg_id' => $head->sppg_id,
            'role' => UserRole::PIC_SEKOLAH,
            'school_id' => null,
            'is_active' => true,
        ]);

        $payload = [
            'school_code' => '20599901',
            'name' => 'SD Negeri Harapan Bangsa',
            'education_level' => 'SD',
            'address' => 'Jl. Ketintang Baru No. 88, Surabaya',
            'latitude' => -7.312345,
            'longitude' => 112.723456,
            'registered_beneficiaries' => 320,
            'public_slug' => 'sdn-harapan-bangsa',
            'pic_user_id' => $candidate->id,
        ];

        $response = $this->actingAs($head)->post('/head/schools', $payload);

        $response->assertRedirect('/head/schools');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('schools', [
            'school_code' => '20599901',
            'name' => 'SD Negeri Harapan Bangsa',
            'education_level' => 'SD',
            'sppg_id' => $head->sppg_id,
            'registered_beneficiaries' => 320,
            'public_slug' => 'sdn-harapan-bangsa',
            'is_active' => 1,
        ]);

        // Verifikasi bahwa user PIC berhasil di-assign ke sekolah baru
        $newSchool = School::where('school_code', '20599901')->first();
        $this->assertNotNull($newSchool);
        $this->assertEquals($newSchool->id, $candidate->fresh()->school_id);
    }

    public function test_store_school_auto_generates_slug_if_empty(): void
    {
        $head = User::where('email', 'kepala@mbgtrack.test')->first();

        $payload = [
            'school_code' => '20588802',
            'name' => 'SMP Kartini Mandiri',
            'education_level' => 'SMP',
            'address' => 'Jl. Pahlawan No. 45, Surabaya',
            'latitude' => -7.260000,
            'longitude' => 112.740000,
            'registered_beneficiaries' => 250,
            'public_slug' => null,
        ];

        $response = $this->actingAs($head)->post('/head/schools', $payload);

        $response->assertRedirect('/head/schools');

        $this->assertDatabaseHas('schools', [
            'school_code' => '20588802',
            'public_slug' => 'smp-kartini-mandiri',
        ]);
    }

    public function test_store_school_validation_errors(): void
    {
        $head = User::where('email', 'kepala@mbgtrack.test')->first();

        // 1. Duplicate school_code & invalid latitude
        $payload = [
            'school_code' => 'SCH-SDN-01', // already used by SDN Percobaan 01
            'name' => 'SD Duplikat',
            'education_level' => 'SD',
            'address' => 'Jl. Testing',
            'latitude' => 105.000000, // Invalid: exceeds 90
            'longitude' => 112.0,
            'registered_beneficiaries' => -10, // Invalid: negative
        ];

        $response = $this->actingAs($head)->post('/head/schools', $payload);

        $response->assertSessionHasErrors([
            'school_code',
            'latitude',
            'registered_beneficiaries',
        ]);
    }

    public function test_head_can_view_school_detail(): void
    {
        $head = User::where('email', 'kepala@mbgtrack.test')->first();
        $school = School::where('school_code', 'SCH-SDN-01')->first();

        $response = $this->actingAs($head)->get("/head/schools/{$school->id}");

        $response->assertStatus(200);
        $response->assertSee('SDN Percobaan 01 Surabaya');
        $response->assertSee('SCH-SDN-01');
        $response->assertSee('Person in Charge');
    }

    public function test_head_cannot_view_or_edit_school_from_different_sppg(): void
    {
        $head = User::where('email', 'kepala@mbgtrack.test')->first();

        // Buat SPPG kedua dan sekolah miliknya
        $otherSppg = Sppg::create([
            'code' => 'SPPG-MLG-01',
            'name' => 'SPPG Wilayah Malang Kota',
            'address' => 'Jl. Ijen No. 10, Malang',
            'latitude' => -7.970000,
            'longitude' => 112.630000,
            'contact_phone' => '0341555123',
            'max_daily_portions' => 2000,
            'is_active' => true,
        ]);

        $otherSchool = School::create([
            'sppg_id' => $otherSppg->id,
            'school_code' => '20577701',
            'name' => 'SDN Klojen Malang',
            'education_level' => 'SD',
            'address' => 'Jl. Klojen No. 5, Malang',
            'latitude' => -7.975000,
            'longitude' => 112.635000,
            'registered_beneficiaries' => 180,
            'public_slug' => 'sdn-klojen-malang',
            'is_active' => true,
        ]);

        // Akses view sekolah dari SPPG lain harus 403 Forbidden
        $responseView = $this->actingAs($head)->get("/head/schools/{$otherSchool->id}");
        $responseView->assertStatus(403);

        // Akses edit sekolah dari SPPG lain harus 403 Forbidden
        $responseEdit = $this->actingAs($head)->get("/head/schools/{$otherSchool->id}/edit");
        $responseEdit->assertStatus(403);

        // Update sekolah dari SPPG lain harus 403 Forbidden
        $responseUpdate = $this->actingAs($head)->put("/head/schools/{$otherSchool->id}", [
            'school_code' => '20577701',
            'name' => 'SDN Klojen Hijacked',
            'education_level' => 'SD',
            'address' => 'Jl. Klojen No. 5, Malang',
        ]);
        $responseUpdate->assertStatus(403);
    }

    public function test_head_can_update_school_and_reassign_pic(): void
    {
        $head = User::where('email', 'kepala@mbgtrack.test')->first();
        $school = School::where('school_code', 'SCH-SDN-01')->first();

        // Buat user baru sebagai calon PIC pengganti
        $newPicCandidate = User::factory()->create([
            'sppg_id' => $head->sppg_id,
            'role' => UserRole::PIC_SEKOLAH,
            'school_id' => null,
            'is_active' => true,
        ]);

        $updatePayload = [
            'school_code' => 'SCH-SDN-01',
            'name' => 'SDN Percobaan 01 Surabaya (Updated)',
            'education_level' => 'SD',
            'address' => 'Jl. Ketintang Baru No. 12 (Pintu Barat), Surabaya',
            'latitude' => -7.257500,
            'longitude' => 112.752100,
            'registered_beneficiaries' => 450,
            'public_slug' => 'sdn-percobaan-01-surabaya',
            'pic_user_id' => $newPicCandidate->id,
            'is_active' => 1,
        ];

        $response = $this->actingAs($head)->put("/head/schools/{$school->id}", $updatePayload);

        $response->assertRedirect('/head/schools');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'name' => 'SDN Percobaan 01 Surabaya (Updated)',
            'registered_beneficiaries' => 450,
        ]);

        // Verifikasi PIC lama dilepas dan PIC baru ditugaskan
        $this->assertEquals($school->id, $newPicCandidate->fresh()->school_id);
    }

    public function test_head_can_toggle_school_active_status_non_destructively(): void
    {
        $head = User::where('email', 'kepala@mbgtrack.test')->first();
        $school = School::where('school_code', 'SCH-SDN-01')->first();
        $this->assertTrue((bool) $school->is_active);

        // 1. Nonaktifkan sekolah
        $responseDeactivate = $this->actingAs($head)->patch("/head/schools/{$school->id}/toggle-active");
        $responseDeactivate->assertRedirect('/head/schools');
        $this->assertFalse((bool) $school->fresh()->is_active);

        // Verifikasi sekolah TIDAK dihapus dari database (historical non-destructive contract)
        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'is_active' => 0,
        ]);

        // 2. Aktifkan kembali
        $responseActivate = $this->actingAs($head)->patch("/head/schools/{$school->id}/toggle-active");
        $responseActivate->assertRedirect('/head/schools');
        $this->assertTrue((bool) $school->fresh()->is_active);
    }
}
