<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\Sppg;
use App\Models\User;
use Database\Seeders\MbgTrackBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BaselineDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MbgTrackBaselineSeeder::class);
    }

    public function test_baseline_seeder_establishes_correct_foundation_data(): void
    {
        // 1 SPPG
        $this->assertEquals(1, Sppg::count());
        $sppg = Sppg::first();
        $this->assertEquals('SPPG-SUB-01', $sppg->code);

        // 3 Sekolah
        $this->assertEquals(3, School::count());

        $schoolA = School::where('school_code', 'SCH-SDN-01')->first();
        $this->assertNotNull($schoolA);
        $this->assertEquals(340, $schoolA->registered_beneficiaries);

        $schoolB = School::where('school_code', 'SCH-SMPN-02')->first();
        $this->assertNotNull($schoolB);
        $this->assertEquals(276, $schoolB->registered_beneficiaries);

        $schoolC = School::where('school_code', 'SCH-SMAN-05')->first();
        $this->assertNotNull($schoolC);
        $this->assertEquals(410, $schoolC->registered_beneficiaries);

        // Total 1.026 porsi
        $totalPortions = School::sum('registered_beneficiaries');
        $this->assertEquals(1026, $totalPortions);

        // 5 internal roles represented
        $rolesPresent = User::pluck('role')->unique()->all();
        $this->assertContains(UserRole::KEPALA_SPPG, $rolesPresent);
        $this->assertContains(UserRole::PENGAWAS_GIZI, $rolesPresent);
        $this->assertContains(UserRole::ASISTEN_LAPANGAN, $rolesPresent);
        $this->assertContains(UserRole::KURIR, $rolesPresent);
        $this->assertContains(UserRole::PIC_SEKOLAH, $rolesPresent);

        // Demo user password verified
        $kepala = User::where('email', 'kepala@mbgtrack.test')->first();
        $this->assertTrue(Hash::check('password', $kepala->password));
    }
}
