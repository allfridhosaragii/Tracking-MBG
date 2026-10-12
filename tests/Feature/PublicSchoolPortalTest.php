<?php

namespace Tests\Feature;

use App\Models\School;
use Database\Seeders\MbgTrackBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSchoolPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MbgTrackBaselineSeeder::class);
    }

    public function test_public_school_page_is_accessible_without_authentication(): void
    {
        $school = School::where('school_code', 'SCH-SDN-01')->first();

        $response = $this->get(route('public.schools.show', $school->public_slug));

        $response->assertStatus(200);
        $response->assertSee($school->name);
        $response->assertSee('Portal Transparansi Publik MBG');
        $response->assertSee('340');
    }

    public function test_public_school_json_endpoint_whitelists_data_and_prevents_leaks(): void
    {
        $school = School::where('school_code', 'SCH-SDN-01')->first();

        $response = $this->getJson(route('public.schools.show', $school->public_slug));

        $response->assertStatus(200);
        $response->assertJson([
            'name' => $school->name,
            'school_code' => $school->school_code,
            'education_level' => $school->education_level,
            'beneficiaries_count' => 340,
        ]);

        // Verify sensitive fields are strictly not present
        $response->assertJsonMissing(['password']);
        $response->assertJsonMissing(['latitude']);
        $response->assertJsonMissing(['longitude']);
        $response->assertJsonMissing(['phone']);
    }

    public function test_non_existent_school_slug_returns_404(): void
    {
        $response = $this->get('/public/schools/non-existent-slug-xyz');

        $response->assertStatus(404);
    }

    public function test_inactive_school_is_not_publicly_visible(): void
    {
        $school = School::where('school_code', 'SCH-SDN-01')->first();
        $school->update(['is_active' => false]);

        $response = $this->get(route('public.schools.show', $school->public_slug));

        $response->assertStatus(404);
    }
}
