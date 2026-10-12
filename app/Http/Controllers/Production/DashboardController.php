<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the Asisten Lapangan Production Runbook dashboard.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $sppg = $user->sppg;

        $schools = School::where('sppg_id', $user->sppg_id)
            ->where('is_active', true)
            ->get();

        $targetPortions = $schools->sum('registered_beneficiaries');

        return view('dashboard.production', [
            'user' => $user,
            'sppg' => $sppg,
            'schools' => $schools,
            'targetPortions' => $targetPortions,
        ]);
    }
}
