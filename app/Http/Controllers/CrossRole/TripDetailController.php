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

        $closureService = app(\App\Services\TripClosureService::class);
        $closureReadiness = $closureService->getClosureReadiness($trip);

        return view('trips.show', [
            'trip' => $trip,
            'isPic' => $isPic,
            'userSchoolId' => $user->school_id,
            'closureReadiness' => $closureReadiness,
        ]);
    }

    /**
     * Provide role-scoped JSON Map Data for MapLibre multi-stop visualization.
     * HIGH GATE D: Enforces strict field-level authorization:
     * - KEPALA_SPPG / ASISTEN_LAPANGAN: Full operational detail.
     * - KURIR: Mission details for assigned driver.
     * - PIC_SEKOLAH: Strict isolation; masks other schools' portions, addresses, and internal driver/vehicle details.
     */
    public function mapData(Request $request, Trip $trip): \Illuminate\Http\JsonResponse
    {
        Gate::authorize('view', $trip);

        $trip->loadMissing([
            'sppg',
            'stops.school',
            'stops.deliveryItems.foodBatch',
            'driver',
            'vehicle',
        ]);

        $user = $request->user();
        $isPic = $user->role === UserRole::PIC_SEKOLAH;
        $isDriver = $user->role === UserRole::KURIR;
        $isSppgStaff = in_array($user->role, [UserRole::KEPALA_SPPG, UserRole::ASISTEN_LAPANGAN, UserRole::PENGAWAS_GIZI], true);

        $sppg = $trip->sppg;
        $stops = $trip->stops->sortBy('sequence_number')->values();

        $activeStop = $stops->first(function ($stop) {
            return in_array($stop->status, [
                \App\Enums\TripStopStatus::EN_ROUTE,
                \App\Enums\TripStopStatus::ARRIVED,
                \App\Enums\TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
                \App\Enums\TripStopStatus::PENDING,
            ], true);
        }) ?: $stops->last();

        $geometry = $trip->route_geometry ? json_decode($trip->route_geometry, true) : null;
        $legs = [];

        // GATE 10: GET requests must NOT mutate database state. Read-only fallback from OSRM cache.
        try {
            $routingService = app(\App\Services\OsrmRoutingService::class);
            $waypoints = $routingService->extractTripWaypoints($trip);
            if (count($waypoints) >= 2) {
                $routeResult = $routingService->getRoute($waypoints);
                if ($routeResult['success']) {
                    if (! $geometry) {
                        $geometry = $routeResult['geometry'] ?? null;
                    }
                    $legs = $routeResult['legs'] ?? [];
                }
            }
        } catch (\Throwable $e) {
            // Honest fallback: null geometry if OSRM unreachable
        }

        // Base payload common to authorized roles
        $response = [
            'trip_id' => $trip->id,
            'trip_code' => $trip->trip_code,
            'status' => $trip->status->value,
            'status_label' => $trip->status->label(),
            'service_date' => $trip->service_date->toDateString(),
            'return_started_at' => $trip->return_started_at?->toIso8601String(),
            'arrived_at_sppg_at' => $trip->arrived_at_sppg_at?->toIso8601String(),
            'completed_at' => $trip->completed_at?->toIso8601String(),
            'is_returning' => $trip->isReturning(),
            'has_arrived_depot' => $trip->hasArrivedAtDepot(),
            'sppg' => [
                'id' => $sppg->id,
                'name' => $sppg->name,
                'lat' => (float) $sppg->latitude,
                'lng' => (float) $sppg->longitude,
            ],
            'active_stop_sequence' => $activeStop?->sequence_number ?? 1,
            'active_stop_id' => $activeStop?->id,
            'geometry' => $geometry,
            'legs' => $legs,
            'total_distance_m' => $trip->total_distance_m,
            'estimated_duration_s' => $trip->estimated_duration_s,
        ];

        if ($isPic) {
            // GATE C: Sanitize for PIC Sekolah (their school only; mask other schools & internal vehicle/driver details)
            // Never expose other schools' lat/lng or full route geometry to PIC.
            $userSchoolId = (int) $user->school_id;

            $response['vehicle'] = null;
            $response['driver'] = null;
            $response['driver_telemetry'] = null;
            $response['geometry'] = null;
            $response['legs'] = [];
            $response['stops'] = $stops->map(function ($stop) use ($userSchoolId, $activeStop) {
                $isOwnSchool = (int) $stop->school_id === $userSchoolId;

                return [
                    'id' => $stop->id,
                    'sequence_number' => $stop->sequence_number,
                    'school_id' => $isOwnSchool ? $stop->school_id : null,
                    'school_name' => $isOwnSchool ? $stop->school->name : "Pemberhentian #{$stop->sequence_number}",
                    'school_address' => $isOwnSchool ? $stop->school->address : null,
                    'lat' => $isOwnSchool ? (float) $stop->school->latitude : null,
                    'lng' => $isOwnSchool ? (float) $stop->school->longitude : null,
                    'planned_portions' => $isOwnSchool ? ($stop->planned_portions ?: $stop->total_loaded_portions) : null,
                    'loaded_portions' => $isOwnSchool ? $stop->loaded_portions : null,
                    'status' => $stop->status->value,
                    'status_label' => $stop->status->label(),
                    'is_active' => $activeStop && (int) $activeStop->id === (int) $stop->id,
                    'is_my_school' => $isOwnSchool,
                ];
            });
        } elseif ($isDriver) {
            $trackingService = app(\App\Services\GpsTrackingService::class);

            // Kurir perspective
            $response['vehicle'] = [
                'name' => $trip->vehicle?->vehicle_name,
                'plate_number' => $trip->vehicle?->plate_number,
            ];
            $response['driver'] = [
                'name' => $trip->driver?->name,
            ];
            $response['driver_telemetry'] = $trackingService->getDriverTrackingStatus($trip);
            $response['stops'] = $stops->map(function ($stop) use ($activeStop) {
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
            });
        } else {
            // Full SPPG Staff / Kepala SPPG perspective
            $trackingService = app(\App\Services\GpsTrackingService::class);
            $response['vehicle'] = [
                'name' => $trip->vehicle?->vehicle_name,
                'plate_number' => $trip->vehicle?->plate_number,
                'capacity' => $trip->vehicle?->capacity_portions,
            ];
            $response['driver'] = [
                'name' => $trip->driver?->name,
                'phone' => $trip->driver?->phone,
            ];
            $response['driver_telemetry'] = $trackingService->getDriverTrackingStatus($trip);
            $response['stops'] = $stops->map(function ($stop) use ($activeStop) {
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
            });
        }

        return response()->json($response);
    }
}
