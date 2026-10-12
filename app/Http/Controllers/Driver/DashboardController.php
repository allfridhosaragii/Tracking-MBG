<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the Kurir Active Mission dashboard.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $sppg = $user->sppg;

        $schools = School::where('sppg_id', $user->sppg_id)
            ->where('is_active', true)
            ->get();

        return view('dashboard.driver', [
            'user' => $user,
            'sppg' => $sppg,
            'stops' => $schools,
            'totalStops' => $schools->count(),
            'totalPortions' => $schools->sum('registered_beneficiaries'),
        ]);
    }
}
