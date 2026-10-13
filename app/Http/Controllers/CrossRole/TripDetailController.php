<?php

namespace App\Http\Controllers\CrossRole;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TripDetailController extends Controller
{
    /**
     * Display Page C03: Trip, multi-stop dan rute pengantaran.
     * Enforces TripPolicy authorization for cross-role access.
     */
    public function show(Request $request, Trip $trip): View
    {
        Gate::authorize('view', $trip);

        $trip->load([
            'sppg',
            'driver',
            'vehicle',
            'creator',
            'stops.school',
            'stops.deliveryItems.foodBatch.recipeMenu',
        ]);

        $user = $request->user();
        $isPic = $user->role === UserRole::PIC_SEKOLAH;

        return view('trips.show', [
            'trip' => $trip,
            'isPic' => $isPic,
            'userSchoolId' => $user->school_id,
        ]);
    }
}
