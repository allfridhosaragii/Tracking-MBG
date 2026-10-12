<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the PIC Sekolah Receipt Workflow dashboard.
     * Strictly scoped to the authenticated user's assigned school.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $school = $user->school;

        if (! $school) {
            abort(403, 'Akun Anda belum dikaitkan dengan unit sekolah penerima MBG.');
        }

        return view('dashboard.school', [
            'user' => $user,
            'school' => $school,
            'sppg' => $school->sppg,
        ]);
    }
}
