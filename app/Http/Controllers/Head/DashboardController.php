<?php

namespace App\Http\Controllers\Head;

use App\Enums\IncidentStatus;
use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\ProductionPlan;
use App\Models\School;
use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;
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
        $today = Carbon::today('Asia/Jakarta')->format('Y-m-d');

        $schools = School::where('sppg_id', $user->sppg_id)
            ->where('is_active', true)
            ->with(['picUsers' => fn ($q) => $q->where('is_active', true)])
            ->get();

        $totalPortions = $schools->sum('registered_beneficiaries');
        $staffCount = User::where('sppg_id', $user->sppg_id)
            ->where('is_active', true)
            ->count();

        $todayPlan = ProductionPlan::where('sppg_id', $user->sppg_id)
            ->whereDate('service_date', $today)
            ->with('menu')
            ->latest('id')
            ->first();

        $todayTripsCount = Trip::where('sppg_id', $user->sppg_id)
            ->whereDate('service_date', $today)
            ->count();

        $openIncidentsCount = Incident::where('sppg_id', $user->sppg_id)
            ->whereNotIn('status', [IncidentStatus::RESOLVED->value, IncidentStatus::CLOSED->value])
            ->count();

        return view('dashboard.head', [
            'user' => $user,
            'sppg' => $sppg,
            'schools' => $schools,
            'totalPortions' => $totalPortions,
            'staffCount' => $staffCount,
            'todayPlan' => $todayPlan,
            'todayTripsCount' => $todayTripsCount,
            'openIncidentsCount' => $openIncidentsCount,
        ]);
    }
}
