<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicDeliveryMapController extends Controller
{
    /**
     * Display sanitized public delivery map view (Page U03 / Public Route Status).
     * Accessible to guest users without authentication only for departed/completed trips.
     */
    public function show(string $tripCode): View
    {
        $trip = Trip::where('trip_code', $tripCode)
            ->with(['sppg', 'stops.school'])
            ->firstOrFail();

        // HIGH GATE F: Public Map Publication Policy.
        // Pre-departure trips (ASSIGNED, VEHICLE_CHECKED, READY) or cancelled trips are not published to the public.
        if (! in_array($trip->status, [\App\Enums\TripStatus::IN_PROGRESS, \App\Enums\TripStatus::COMPLETED], true)) {
            abort(404, 'Informasi rute pengantaran belum dipublikasikan atau sudah tidak aktif.');
        }

        return view('public.delivery-map', compact('trip'));
    }

    /**
     * Provide sanitized JSON map data for public map view.
     * Rate-limited and read-only.
     */
    public function mapData(string $tripCode): JsonResponse
    {
        $trip = Trip::where('trip_code', $tripCode)
            ->with(['sppg', 'stops.school'])
            ->firstOrFail();

        // HIGH GATE F: Publication policy enforcement
        if (! in_array($trip->status, [\App\Enums\TripStatus::IN_PROGRESS, \App\Enums\TripStatus::COMPLETED], true)) {
            abort(404, 'Informasi rute pengantaran belum dipublikasikan atau sudah tidak aktif.');
        }

        $sppg = $trip->sppg;
        $stops = $trip->stops->sortBy('sequence_number')->values();

        $activeStop = $stops->first(function ($stop) {
            return in_array($stop->status, [
                \App\Enums\TripStopStatus::EN_ROUTE,
                \App\Enums\TripStopStatus::ARRIVED,
                \App\Enums\TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
                \App\Enums\TripStopStatus::PENDING,
            ], true);
        }) ?: $stops->first();

        // HIGH GATE G: Complete match expression for TripStatus with safe fallback
        $publicStage = match ($trip->status) {
            \App\Enums\TripStatus::ASSIGNED,
            \App\Enums\TripStatus::VEHICLE_CHECKED,
            \App\Enums\TripStatus::READY => 'Persiapan Pengantaran di Dapur SPPG',
            \App\Enums\TripStatus::IN_PROGRESS => 'Armada Sedang dalam Perjalanan Menuju Sekolah Tujuan',
            \App\Enums\TripStatus::COMPLETED => 'Pengantaran Selesai',
            \App\Enums\TripStatus::CANCELLED => 'Jadwal Pengantaran Dibatalkan',
            default => 'Status Pengantaran Sedang Diproses',
        };

        $geometry = $trip->route_geometry ? json_decode($trip->route_geometry, true) : null;
        try {
            $routingService = app(\App\Services\OsrmRoutingService::class);
            $waypoints = $routingService->extractTripWaypoints($trip);
            if (count($waypoints) >= 2) {
                $routeResult = $routingService->getRoute($waypoints);
                if ($routeResult['success'] && ! $geometry) {
                    $geometry = $routeResult['geometry'] ?? null;
                }
            }
        } catch (\Throwable $e) {
            // Graceful fallback
        }

        return response()->json([
            'trip_code' => $trip->trip_code,
            'service_date' => $trip->service_date->translatedFormat('d F Y'),
            'delivery_stage' => $publicStage,
            'status' => $trip->status->value,
            'status_label' => $trip->status->label(),
            'last_updated' => ($trip->updated_at ?? $trip->created_at)->toIso8601String(),
            'sppg' => [
                'name' => $sppg->name,
                'lat' => (float) $sppg->latitude,
                'lng' => (float) $sppg->longitude,
            ],
            'stops' => $stops->map(function ($stop) use ($activeStop) {
                // HIGH GATE G: Complete match expression for TripStopStatus with safe fallback
                $stopStage = match ($stop->status) {
                    \App\Enums\TripStopStatus::PENDING => 'Menunggu Giliran Pengantaran',
                    \App\Enums\TripStopStatus::EN_ROUTE => 'Armada Sedang Menuju Sekolah Ini',
                    \App\Enums\TripStopStatus::ARRIVED => 'Armada Telah Tiba di Lokasi Sekolah',
                    \App\Enums\TripStopStatus::ARRIVED_AWAITING_CONFIRMATION => 'Armada Telah Tiba, Menunggu Konfirmasi Sekolah',
                    \App\Enums\TripStopStatus::HANDED_OVER => 'Makanan Telah Diserahkan ke Pihak Sekolah',
                    \App\Enums\TripStopStatus::COMPLETED => 'Pengantaran ke Sekolah Ini Selesai',
                    \App\Enums\TripStopStatus::SKIPPED => 'Pengantaran Dilewati',
                    \App\Enums\TripStopStatus::EXCEPTION => 'Terdapat Kendala Pengantaran',
                    default => 'Status Pengantaran Belum Diketahui',
                };

                return [
                    'sequence_number' => $stop->sequence_number,
                    'school_name' => $stop->school->name,
                    'education_level' => $stop->school->education_level,
                    'lat' => (float) $stop->school->latitude,
                    'lng' => (float) $stop->school->longitude,
                    'status' => $stop->status->value,
                    'status_label' => $stop->status->label(),
                    'stage' => $stopStage,
                    'is_current_target' => $activeStop && (int) $activeStop->id === (int) $stop->id,
                ];
            }),
            'geometry' => $geometry,
            'total_distance_m' => $trip->total_distance_m,
        ]);
    }
}
