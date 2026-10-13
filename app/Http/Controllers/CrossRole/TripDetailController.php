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

    /**
     * Provide JSON Map Data for MapLibre multi-stop visualization.
     */
    public function mapData(Trip $trip): \Illuminate\Http\JsonResponse
    {
        Gate::authorize('view', $trip);

        $trip->loadMissing([
            'sppg',
            'stops.school',
            'stops.deliveryItems.foodBatch',
            'driver',
            'vehicle',
        ]);

        $sppg = $trip->sppg;
        $stops = $trip->stops->sortBy('sequence_number')->values();

        $activeStop = $stops->first(function ($stop) {
            return in_array($stop->status, [\App\Enums\TripStopStatus::EN_ROUTE, \App\Enums\TripStopStatus::PENDING], true);
        }) ?: $stops->first();

        $geometry = $trip->route_geometry ? json_decode($trip->route_geometry, true) : null;

        if (! $geometry) {
            try {
                $routeResult = app(\App\Services\OsrmRoutingService::class)->calculateTripRoute($trip);
                $geometry = $routeResult['geometry'] ?? null;
            } catch (\Throwable $e) {
                // Honest fallback: null geometry if OSRM unreachable
            }
        }

        return response()->json([
            'trip_id' => $trip->id,
            'trip_code' => $trip->trip_code,
            'status' => $trip->status->value,
            'status_label' => $trip->status->label(),
            'service_date' => $trip->service_date->toDateString(),
            'sppg' => [
                'id' => $sppg->id,
                'name' => $sppg->name,
                'lat' => (float) $sppg->latitude,
                'lng' => (float) $sppg->longitude,
            ],
            'vehicle' => [
                'name' => $trip->vehicle?->vehicle_name,
                'plate_number' => $trip->vehicle?->plate_number,
                'capacity' => $trip->vehicle?->capacity_portions,
            ],
            'driver' => [
                'name' => $trip->driver?->name,
            ],
            'stops' => $stops->map(function ($stop) use ($activeStop) {
                return [
                    'id' => $stop->id,
                    'sequence_number' => $stop->sequence_number,
                    'school_id' => $stop->school_id,
                    'school_name' => $stop->school->name,
                    'school_address' => $stop->school->address,
                    'lat' => (float) $stop->school->latitude,
                    'lng' => (float) $stop->school->longitude,
                    'planned_portions' => $stop->planned_portions ?: $stop->total_loaded_portions,
                    'loaded_portions' => $stop->loaded_portions,
                    'status' => $stop->status->value,
                    'status_label' => $stop->status->label(),
                    'is_active' => $activeStop && (int) $activeStop->id === (int) $stop->id,
                ];
            }),
            'active_stop_sequence' => $activeStop?->sequence_number ?? 1,
            'geometry' => $geometry,
            'total_distance_m' => $trip->total_distance_m,
            'estimated_duration_s' => $trip->estimated_duration_s,
        ]);
    }
}
