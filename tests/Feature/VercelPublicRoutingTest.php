<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Sppg;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class VercelPublicRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalized_script_name_prevents_base_url_stripping_on_public_routes(): void
    {
        $sppg = Sppg::create([
            'code' => 'SPPG-VERCEL-01',
            'name' => 'SPPG Uji Vercel',
            'address' => 'Jl. Raya Uji 1',
            'is_active' => true,
        ]);

        School::create([
            'sppg_id' => $sppg->id,
            'school_code' => 'SCH-VERCEL-01',
            'name' => 'SDN Uji Vercel 01',
            'education_level' => 'SD',
            'address' => 'Jl. Sekolah 1',
            'beneficiaries_count' => 250,
            'public_slug' => 'sdn-uji-vercel-01',
            'is_active' => true,
        ]);

        $response = $this->withServerVariables([
            'SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
            'SCRIPT_FILENAME' => base_path('public/index.php'),
            'HTTPS' => 'on',
        ])->get('/public/schools/sdn-uji-vercel-01');

        $response->assertStatus(200);
        $response->assertSee('SDN Uji Vercel 01');
        $response->assertDontSee('/public/build/', false);

        $request = Request::create('/public/schools/sdn-uji-vercel-01', 'GET', [], [], [], [
            'SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
            'SCRIPT_FILENAME' => base_path('public/index.php'),
        ]);

        $this->assertSame('', $request->getBaseUrl());
        $this->assertSame('/public/schools/sdn-uji-vercel-01', $request->getPathInfo());
    }
}
