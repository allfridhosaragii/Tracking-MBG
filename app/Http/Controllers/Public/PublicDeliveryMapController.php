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
     * Accessible to guest users without authentication.
     */
    public function show(string $tripCode): View
    {
        $trip = Trip::where('trip_code', $tripCode)
            ->with(['sppg', 'stops.school'])
            ->firstOrFail();

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

        $sppg = $trip->sppg;
        $stops = $trip->stops->sortBy('sequence_number')->values();

        $activeStop = $stops->first(function ($stop) {
            return in_array($stop->status, [\App\Enums\TripStopStatus::EN_ROUTE, \App\Enums\TripStopStatus::PENDING], true);
        }) ?: $stops->first();

        // Public stage description based purely on valid operational events
        $publicStage = match ($trip->status) {
            \App\Enums\TripStatus::ASSIGNED,
            \App\Enums\TripStatus::VEHICLE_CHECKED,
            \App\Enums\TripStatus::READY => 'Persiapan Pengantaran di Dapur SPPG',
            \App\Enums\TripStatus::IN_PROGRESS => 'Armada Sedang dalam Perjalanan Menuju Sekolah Tujuan',
            \App\Enums\TripStatus::COMPLETED => 'Pengantaran Selesai',
            \App\Enums\TripStatus::CANCELLED => 'Jadwal Pengantaran Dibatalkan',
            default => 'Dalam Proses',
        };

        $geometry = $trip->route_geometry ? json_decode($trip->route_geometry, true) : null;

        return response()->json([
            'trip_code' => $trip->trip_code,
            'service_date' => $trip->service_date->translatedFormat('d F Y'),
            'delivery_stage' => $publicStage,
            'status' => $trip->status->value,
            'sppg' => [
                'name' => $sppg->name,
                'lat' => (float) $sppg->latitude,
                'lng' => (float) $sppg->longitude,
            ],
            'stops' => $stops->map(function ($stop) use ($activeStop) {
                // Public label per stop
                $stopStage = match ($stop->status) {
                    \App\Enums\TripStopStatus::PENDING => 'Menunggu Giliran Pengantaran',
                    \App\Enums\TripStopStatus::EN_ROUTE => 'Armada Sedang Menuju Sekolah Ini',
                    \App\Enums\TripStopStatus::ARRIVED => 'Armada Telah Tiba di Lokasi Sekolah',
                    \App\Enums\TripStopStatus::HANDED_OVER => 'Serah Terima Makanan Selesai',
                    \App\Enums\TripStopStatus::SKIPPED => 'Dilewati',
                };

                return [
                    'sequence_number' => $stop->sequence_number,
                    'school_name' => $stop->school->name,
                    'education_level' => $stop->school->education_level,
                    'lat' => (float) $stop->school->latitude,
                    'lng' => (float) $stop->school->longitude,
                    'stage' => $stopStage,
                    'is_current_target' => $activeStop && (int) $activeStop->id === (int) $stop->id,
                ];
            }),
            'geometry' => $geometry,
            'total_distance_m' => $trip->total_distance_m,
        ]);
    }
}
