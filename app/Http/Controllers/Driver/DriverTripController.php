<?php

namespace App\Http\Controllers\Driver;

use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\VehicleCheckRequest;
use App\Models\Trip;
use App\Services\TripDispatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DriverTripController extends Controller
{
    public function __construct(
        protected TripDispatchService $tripDispatchService
    ) {}

    /**
     * Display trips assigned to the logged-in driver (Page D01).
     */
    public function index(Request $request): View
    {
        $driver = $request->user();

        $trips = Trip::forDriver($driver->id)
            ->with(['vehicle', 'stops.school', 'stops.deliveryItems.foodBatch'])
            ->latest('service_date')
            ->get();

        $activeTrips = $trips->filter(function ($trip) {
            return in_array($trip->status, [
                TripStatus::ASSIGNED,
                TripStatus::VEHICLE_CHECKED,
                TripStatus::READY,
                TripStatus::IN_PROGRESS,
            ], true);
        });

        $completedTrips = $trips->filter(function ($trip) {
            return in_array($trip->status, [
                TripStatus::COMPLETED,
                TripStatus::CANCELLED,
            ], true);
        });

        return view('driver.trips.index', compact('activeTrips', 'completedTrips'));
    }

    /**
     * Pre-trip preparation workspace & vehicle inspection checklist (Page D02).
     */
    public function prepare(Request $request, Trip $trip): View
    {
        Gate::authorize('checkVehicle', $trip);

        $trip->load(['vehicle', 'stops.school', 'stops.deliveryItems.foodBatch.recipeMenu']);

        return view('driver.trips.prepare', compact('trip'));
    }

    /**
     * Submit vehicle safety checklist.
     */
    public function vehicleCheck(VehicleCheckRequest $request, Trip $trip): RedirectResponse
    {
        Gate::authorize('checkVehicle', $trip);

        $this->tripDispatchService->checkVehicle($trip, $request->validated(), $request->user());

        $passed = (bool) $request->input('passed');
        $msg = $passed
            ? 'Pemeriksaan armada berhasil dicatat. Kendaraan siap berangkat.'
            : 'Pemeriksaan armada mencatat ketidaklayakan. Hubungi Kepala SPPG untuk armada pengganti.';

        return redirect()
            ->route('driver.trips.prepare', $trip)
            ->with($passed ? 'success' : 'error', $msg);
    }

    /**
     * Start the trip dispatch (Swipe-to-depart).
     * Supports both JSON response for AJAX swipe slider and redirect for conventional forms.
     */
    public function start(Request $request, Trip $trip): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        Gate::authorize('start', $trip);

        // Status reconciliation: If connection dropped after commit and kurir retried
        if ($trip->status === TripStatus::IN_PROGRESS) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Perjalanan sudah berstatus aktif berjalan.',
                    'trip_status' => $trip->status->value,
                    'started_at' => $trip->started_at?->toIso8601String(),
                    'tracking_status' => $trip->tracking_status,
                ], 200);
            }

            return redirect()
                ->route('driver.trips.prepare', $trip)
                ->with('info', 'Perjalanan sudah berstatus aktif berjalan.');
        }

        // Validate trip readiness before service invocation
        if ($trip->status !== TripStatus::READY) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perjalanan belum siap dimulai. Pastikan seluruh gerbang verifikasi (QC, muatan fisik, kelaikan armada, dan otorisasi Kepala SPPG) telah terpenuhi.',
                    'trip_status' => $trip->status->value,
                ], 422);
            }

            return redirect()
                ->route('driver.trips.prepare', $trip)
                ->with('error', 'Perjalanan belum berstatus siap berangkat.');
        }

        $startedTrip = $this->tripDispatchService->startTrip($trip, $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Perjalanan pengantaran telah resmi dimulai. Menuju pemberhentian pertama.',
                'trip_status' => $startedTrip->status->value,
                'started_at' => $startedTrip->started_at?->toIso8601String(),
                'tracking_status' => $startedTrip->tracking_status,
                'active_url' => route('driver.trips.active', $startedTrip),
            ], 200);
        }

        return redirect()
            ->route('driver.trips.prepare', $startedTrip)
            ->with('success', 'Perjalanan pengantaran telah resmi dimulai. Menuju pemberhentian pertama.');
    }

    /**
     * Active trip navigation & real-time GPS telemetry sharing screen (Page D03).
     */
    public function active(Request $request, Trip $trip): View
    {
        Gate::authorize('view', $trip);

        $trip->load(['vehicle', 'sppg', 'stops.school', 'stops.deliveryItems.foodBatch']);

        $trackingService = app(\App\Services\GpsTrackingService::class);
        $trackingStatus = $trackingService->getDriverTrackingStatus($trip);

        $stops = $trip->stops->sortBy('sequence_number')->values();
        $activeStop = $stops->first(function ($stop) {
            return in_array($stop->status, [
                \App\Enums\TripStopStatus::EN_ROUTE,
                \App\Enums\TripStopStatus::ARRIVED,
                \App\Enums\TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
                \App\Enums\TripStopStatus::PENDING,
            ], true);
        }) ?: $stops->last();

        return view('driver.trips.active', [
            'trip' => $trip,
            'stops' => $stops,
            'activeStop' => $activeStop,
            'trackingStatus' => $trackingStatus,
        ]);
    }

    /**
     * Ingest driver's real GPS telemetry point from browser Geolocation API.
     */
    public function telemetry(Request $request, Trip $trip): \Illuminate\Http\JsonResponse
    {
        Gate::authorize('recordTelemetry', $trip);

        $trackingService = app(\App\Services\GpsTrackingService::class);
        $gpsPoint = $trackingService->recordDriverLocation($trip, $request->user(), $request->all());

        $refreshedTrip = $trip->fresh();
        $isStaleAfterStop = $refreshedTrip->tracking_status === 'SHARING_STOPPED';

        return response()->json([
            'success' => true,
            'tracking_status' => $refreshedTrip->tracking_status ?? 'ONLINE',
            'is_stale_telemetry' => $isStaleAfterStop,
            'latitude' => $gpsPoint->latitude,
            'longitude' => $gpsPoint->longitude,
            'accuracy_m' => $gpsPoint->accuracy_m,
            'recorded_at' => $gpsPoint->recorded_at->toIso8601String(),
        ], 200);
    }

    /**
     * Driver explicitly stops GPS telemetry sharing (Gate C).
     * Supports client session sequencing and stopped_at timestamp to prevent race conditions.
     */
    public function stopTelemetry(Request $request, Trip $trip): \Illuminate\Http\JsonResponse
    {
        Gate::authorize('recordTelemetry', $trip);

        $trackingService = app(\App\Services\GpsTrackingService::class);
        $stoppedAt = $request->input('stopped_at');
        $sessionId = $request->input('session_id') ? (int) $request->input('session_id') : null;

        $updatedTrip = $trackingService->stopDriverTracking($trip, $request->user(), $stoppedAt, $sessionId);

        $isStaleIgnored = $updatedTrip->tracking_status !== 'SHARING_STOPPED';

        return response()->json([
            'success' => true,
            'message' => $isStaleIgnored
                ? 'Permintaan penghentian lama diabaikan karena sesi pelacakan baru telah aktif.'
                : 'Berbagi lokasi GPS telah berhasil dihentikan oleh kurir.',
            'tracking_status' => $updatedTrip->tracking_status,
            'is_stale_stop' => $isStaleIgnored,
            'tracking_ended_at' => $updatedTrip->tracking_ended_at?->toIso8601String(),
            'last_latitude' => (float) $updatedTrip->last_latitude,
            'last_longitude' => (float) $updatedTrip->last_longitude,
        ], 200);
    }

    /**
     * Driver telemetry status reconciliation endpoint.
     */
    public function telemetryStatus(Request $request, Trip $trip): \Illuminate\Http\JsonResponse
    {
        Gate::authorize('recordTelemetry', $trip);

        $trackingService = app(\App\Services\GpsTrackingService::class);
        $trackingStatus = $trackingService->getDriverTrackingStatus($trip);

        return response()->json([
            'success' => true,
            'tracking_status' => $trackingStatus['status'],
            'is_stopped' => $trackingStatus['is_stopped'] ?? false,
            'is_online' => $trackingStatus['is_online'] ?? false,
            'is_stale' => $trackingStatus['is_stale'] ?? false,
            'label' => $trackingStatus['label'],
            'badge_class' => $trackingStatus['badge_class'],
            'last_location_at' => $trackingStatus['last_location_at'],
        ], 200);
    }

    /**
     * Driver marks arrival at the active school stop.
     */
    public function arrive(Request $request, Trip $trip, \App\Models\TripStop $stop): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        Gate::authorize('view', $trip);

        $trackingService = app(\App\Services\GpsTrackingService::class);
        $updatedStop = $trackingService->markStopArrived($trip, $stop, $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Tiba di {$updatedStop->school->name}. Menunggu konfirmasi serah terima.",
                'stop_status' => $updatedStop->status->value,
                'arrived_at' => $updatedStop->arrived_at?->toIso8601String(),
            ]);
        }

        return redirect()
            ->route('driver.trips.active', $trip)
            ->with('success', "Tiba di {$updatedStop->school->name}. Menunggu konfirmasi serah terima.");
    }
}


