<?php

namespace App\Http\Controllers\Driver;

use App\Enums\TrayReturnStatus;
use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\TrayReturn;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the Kurir Active Mission dashboard with live trip & pickup status.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $sppg = $user->sppg;

        $schools = School::where('sppg_id', $user->sppg_id)
            ->where('is_active', true)
            ->get();

        $activeTrips = Trip::forDriver((int) $user->id)
            ->whereIn('status', [
                TripStatus::ASSIGNED->value,
                TripStatus::VEHICLE_CHECKED->value,
                TripStatus::READY->value,
                TripStatus::IN_PROGRESS->value,
            ])
            ->with(['vehicle', 'stops.school'])
            ->latest('id')
            ->get();

        $pendingPickupsCount = TrayReturn::query()
            ->whereIn('status', [
                TrayReturnStatus::READY_FOR_PICKUP->value,
                TrayReturnStatus::PICKUP_ASSIGNED->value,
            ])
            ->whereHas('schoolServiceRecord.tripStop.trip', fn ($q) => $q->where('sppg_id', $user->sppg_id))
            ->count();

        return view('dashboard.driver', [
            'user' => $user,
            'sppg' => $sppg,
            'stops' => $schools,
            'totalStops' => $schools->count(),
            'totalPortions' => $schools->sum('registered_beneficiaries'),
            'activeTrips' => $activeTrips,
            'pendingPickupsCount' => $pendingPickupsCount,
        ]);
    }
}
