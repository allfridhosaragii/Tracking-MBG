<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchoolPublicController extends Controller
{
    /**
     * Display the public transparency page for a school.
     * Strictly whitelisted DTO to prevent internal data exposure.
     */
    public function show(Request $request, string $slug): View|JsonResponse
    {
        $school = School::where('public_slug', $slug)
            ->where('is_active', true)
            ->with(['sppg' => function ($query) {
                $query->select('id', 'name', 'code');
            }])
            ->firstOrFail();

        // Whitelisted public projection
        $publicData = [
            'name' => $school->name,
            'school_code' => $school->school_code,
            'education_level' => $school->education_level,
            'beneficiaries_count' => (int) $school->registered_beneficiaries,
            'sppg_partner' => $school->sppg?->name,
            'service_status' => 'TERJADWAL (SIMULASI)',
            'public_notice' => 'Portal Transparansi Program MBG. Data disajikan untuk orang tua dan siswa tanpa informasi privat operasional.',
        ];

        if ($request->wantsJson()) {
            return response()->json($publicData);
        }

        return view('public.school', [
            'school' => $school,
            'data' => $publicData,
        ]);
    }
}
