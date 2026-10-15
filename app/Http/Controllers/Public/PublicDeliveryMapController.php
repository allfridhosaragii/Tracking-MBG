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
    public function show(Request $request, string $tripCode): View
    {
        $trip = Trip::where('trip_code', $tripCode)
            ->with(['sppg', 'stops.school'])
            ->firstOrFail();

        // HIGH GATE F: Public Map Publication Policy.
        // Pre-departure trips (ASSIGNED, VEHICLE_CHECKED, READY) or cancelled trips are not published to the public.
        if (! in_array($trip->status, [\App\Enums\TripStatus::IN_PROGRESS, \App\Enums\TripStatus::COMPLETED], true)) {
            abort(404, 'Informasi rute pengantaran belum dipublikasikan atau sudah tidak aktif.');
        }

        $hasFilter = $request->has('school') || $request->has('school_slug') || $request->has('school_id') || $request->has('filter');
        $schoolQuery = $request->query('school') ?: $request->query('school_slug') ?: $request->query('school_id') ?: $request->query('filter');
        $matchedStop = null;

        if ($schoolQuery) {
            $matchedStop = $trip->stops->first(function ($s) use ($schoolQuery) {
                return (string) $s->school_id === (string) $schoolQuery
                    || $s->school->public_slug === (string) $schoolQuery
                    || $s->school->school_code === (string) $schoolQuery;
            });
        }

        return view('public.delivery-map', compact('trip', 'matchedStop', 'schoolQuery', 'hasFilter'));
    }

    /**
     * Provide sanitized JSON map data for public map view.
     * Rate-limited and read-only.
     * GATE C: Strictly protects privacy - no raw GPS coordinates, no private route geometry,
     * no driver identity, no other schools, and empty/invalid filter NEVER falls back to all stops.
     */
    public function mapData(Request $request, string $tripCode): JsonResponse
    {
        $trip = Trip::where('trip_code', $tripCode)
            ->with(['sppg', 'stops.school'])
            ->firstOrFail();

        // HIGH GATE F: Publication policy enforcement
        if (! in_array($trip->status, [\App\Enums\TripStatus::IN_PROGRESS, \App\Enums\TripStatus::COMPLETED], true)) {
            abort(404, 'Informasi rute pengantaran belum dipublikasikan atau sudah tidak aktif.');
        }

        $sppg = $trip->sppg;
        $allStops = $trip->stops->sortBy('sequence_number')->values();

        $hasFilter = $request->has('school') || $request->has('school_slug') || $request->has('school_id') || $request->has('filter');
        $schoolQuery = $request->query('school') ?: $request->query('school_slug') ?: $request->query('school_id') ?: $request->query('filter');
        $matchedStop = null;

        if ($schoolQuery) {
            $matchedStop = $allStops->first(function ($s) use ($schoolQuery) {
                return (string) $s->school_id === (string) $schoolQuery
                    || $s->school->public_slug === (string) $schoolQuery
                    || $s->school->school_code === (string) $schoolQuery;
            });
        }

        // GATE C: If filtered, only project matched school; if filter empty/invalid, project empty (no fallback leaks).
        // If not filtered (generic trip overview), project all stops with sanitized data.
        if ($hasFilter) {
            $stops = $matchedStop ? collect([$matchedStop]) : collect([]);
            $activeStop = $matchedStop;
        } else {
            $stops = $allStops;
            $activeStop = $allStops->firstWhere('status', \App\Enums\TripStopStatus::EN_ROUTE)
                ?? $allStops->firstWhere('status', \App\Enums\TripStopStatus::ARRIVED)
                ?? $allStops->firstWhere('status', \App\Enums\TripStopStatus::PENDING)
                ?? $allStops->last();
        }

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

        // Telemetry staleness & live status (GATE C)
        $now = \Carbon\Carbon::now();
        $lastLocAt = $trip->last_location_at;
        $isFresh = $lastLocAt && $lastLocAt->diffInMinutes($now) <= 5;
        $isLive = ($trip->status === \App\Enums\TripStatus::IN_PROGRESS)
            && in_array($trip->tracking_status, ['ONLINE', 'WAITING_FOR_LOCATION'], true)
            && $isFresh;
        $isStale = ($trip->status === \App\Enums\TripStatus::IN_PROGRESS) && (! $isFresh || in_array($trip->tracking_status, ['STALE', 'OFFLINE', 'SHARING_STOPPED'], true));

        $latestTimestamp = collect([
            $trip->updated_at,
            $trip->created_at,
            $trip->last_location_at,
            $stops->max('updated_at'),
            $stops->max('arrived_at'),
        ])->filter()->max();

        $lastUpdatedIso = ($latestTimestamp instanceof \Carbon\CarbonInterface)
            ? $latestTimestamp->toIso8601String()
            : ($latestTimestamp ? \Carbon\Carbon::parse($latestTimestamp)->toIso8601String() : ($trip->updated_at ?? $trip->created_at)->toIso8601String());

        return response()->json([
            'trip_code' => $trip->trip_code,
            'service_date' => $trip->service_date->translatedFormat('d F Y'),
            'delivery_stage' => $publicStage,
            'status' => $trip->status->value,
            'status_label' => $trip->status->label(),
            'tracking_status' => $trip->tracking_status,
            'is_live' => $isLive,
            'is_stale' => $isStale,
            'eta_status' => ($isLive && $activeStop?->estimated_arrival_at) ? 'AVAILABLE' : 'UNAVAILABLE',
            'last_updated' => $lastUpdatedIso,
            'sppg' => [
                'name' => $sppg->name,
                'lat' => null, // Privacy: masked from public JSON
                'lng' => null, // Privacy: masked from public JSON
            ],
            'stops' => $stops->map(function ($stop) use ($activeStop, $hasFilter) {
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

                $stopItem = [
                    'school_name' => $stop->school->name,
                    'education_level' => $stop->school->education_level,
                    'lat' => null, // Privacy: masked from public JSON
                    'lng' => null, // Privacy: masked from public JSON
                    'status' => $stop->status->value,
                    'status_label' => $stop->status->label(),
                    'stage' => $stopStage,
                    'is_current_target' => $activeStop && (int) $activeStop->id === (int) $stop->id,
                ];

                if (! $hasFilter) {
                    $stopItem['sequence_number'] = $stop->sequence_number;
                }

                return $stopItem;
            }),
            'geometry' => null, // Privacy: raw route geometry never exposed publicly
            'total_distance_m' => null,
        ]);
    }

    /**
     * Page U04: Minimal public delivery status projection for a specific school.
     * Strictly whitelisted - no GPS, no raw route geometry, no driver identity, no other schools.
     */
    public function schoolDeliveryStatus(Request $request, string $slug): JsonResponse|View
    {
        $school = \App\Models\School::where('public_slug', $slug)
            ->where('is_active', true)
            ->with(['sppg'])
            ->firstOrFail();

        $today = \Carbon\Carbon::today();

        // Find today's delivery stop for this school
        $stop = \App\Models\TripStop::where('school_id', $school->id)
            ->whereHas('trip', function ($q) use ($today) {
                $q->whereDate('service_date', $today)
                    ->whereIn('status', [
                        \App\Enums\TripStatus::ASSIGNED,
                        \App\Enums\TripStatus::VEHICLE_CHECKED,
                        \App\Enums\TripStatus::READY,
                        \App\Enums\TripStatus::IN_PROGRESS,
                        \App\Enums\TripStatus::COMPLETED,
                    ]);
            })
            ->with(['trip', 'schoolServiceRecord'])
            ->latest('id')
            ->first();

        $trip = $stop?->trip;
        $now = \Carbon\Carbon::now();
        $isLive = false;
        $isStale = false;
        $lastLocationAt = $trip?->last_location_at;

        if ($trip && $trip->status === \App\Enums\TripStatus::IN_PROGRESS) {
            $isFresh = $lastLocationAt && $lastLocationAt->diffInMinutes($now) <= 5;
            $isLive = in_array($trip->tracking_status, ['ONLINE', 'WAITING_FOR_LOCATION'], true) && $isFresh;
            $isStale = ! $isFresh || in_array($trip->tracking_status, ['STALE', 'OFFLINE', 'SHARING_STOPPED'], true);
        }

        $stageText = 'Belum Ada Jadwal Pengantaran Aktif Hari Ini';
        $isArrived = false;
        $isReceived = false;

        if ($stop) {
            $isArrived = in_array($stop->status, [
                \App\Enums\TripStopStatus::ARRIVED,
                \App\Enums\TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
                \App\Enums\TripStopStatus::HANDED_OVER,
                \App\Enums\TripStopStatus::COMPLETED,
            ], true);

            $isReceived = in_array($stop->status, [
                \App\Enums\TripStopStatus::HANDED_OVER,
                \App\Enums\TripStopStatus::COMPLETED,
            ], true);

            $stageText = match ($stop->status) {
                \App\Enums\TripStopStatus::PENDING => 'Menunggu Giliran Pengantaran',
                \App\Enums\TripStopStatus::EN_ROUTE => 'Armada Sedang dalam Perjalanan Menuju Sekolah',
                \App\Enums\TripStopStatus::ARRIVED,
                \App\Enums\TripStopStatus::ARRIVED_AWAITING_CONFIRMATION => 'Armada Telah Tiba di Sekolah (Menunggu Pemeriksaan PIC)',
                \App\Enums\TripStopStatus::HANDED_OVER => 'Makanan Telah Diterima oleh Pihak Sekolah',
                \App\Enums\TripStopStatus::COMPLETED => 'Pengantaran Telah Selesai',
                \App\Enums\TripStopStatus::SKIPPED => 'Pengantaran Hari Ini Dilewati',
                \App\Enums\TripStopStatus::EXCEPTION => 'Terdapat Kendala Distribusi',
                default => 'Sedang Diproses',
            };
        }

        $etaText = ($isLive && $stop?->estimated_arrival_at)
            ? $stop->estimated_arrival_at->translatedFormat('H:i') . ' WIB'
            : null;

        $lastUpdated = $stop?->updated_at ?? $trip?->updated_at ?? $now;

        $projection = [
            'school' => [
                'name' => $school->name,
                'school_code' => $school->school_code,
                'education_level' => $school->education_level,
            ],
            'sppg' => [
                'name' => $school->sppg?->name,
            ],
            'delivery_stage' => $stageText,
            'stop_status' => $stop?->status?->value ?? 'NO_ACTIVE_DELIVERY',
            'is_arrived' => $isArrived,
            'is_received' => $isReceived,
            'eta' => $etaText,
            'eta_status' => $etaText ? 'AVAILABLE' : 'UNAVAILABLE',
            'is_live' => $isLive,
            'is_stale' => $isStale,
            'last_updated' => $lastUpdated->toIso8601String(),
        ];

        if ($request->wantsJson()) {
            return response()->json($projection);
        }

        return view('public.school-delivery-status', [
            'school' => $school,
            'stop' => $stop,
            'trip' => $trip,
            'data' => $projection,
        ]);
    }
}
