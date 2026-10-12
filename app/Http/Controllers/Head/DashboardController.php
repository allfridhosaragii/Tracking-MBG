<?php

namespace App\Http\Controllers\Head;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the Kepala SPPG Operations Briefing dashboard.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $sppg = $user->sppg;

        $schools = School::where('sppg_id', $user->sppg_id)
            ->where('is_active', true)
            ->get();

        $totalPortions = $schools->sum('registered_beneficiaries');
        $staffCount = User::where('sppg_id', $user->sppg_id)
            ->where('is_active', true)
            ->count();

        return view('dashboard.head', [
            'user' => $user,
            'sppg' => $sppg,
            'schools' => $schools,
            'totalPortions' => $totalPortions,
            'staffCount' => $staffCount,
        ]);
    }
}
