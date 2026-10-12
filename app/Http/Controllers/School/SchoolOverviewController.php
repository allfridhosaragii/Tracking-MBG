<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SchoolOverviewController extends Controller
{
    /**
     * Display a specific school profile, strictly governed by SchoolPolicy.
     */
    public function show(Request $request, School $school): View|JsonResponse
    {
        Gate::authorize('view', $school);

        if ($request->wantsJson()) {
            return response()->json([
                'id' => $school->id,
                'school_code' => $school->school_code,
                'name' => $school->name,
                'education_level' => $school->education_level,
                'registered_beneficiaries' => $school->registered_beneficiaries,
                'public_slug' => $school->public_slug,
            ]);
        }

        return view('dashboard.school-detail', [
            'user' => $request->user(),
            'school' => $school,
            'sppg' => $school->sppg,
        ]);
    }
}
