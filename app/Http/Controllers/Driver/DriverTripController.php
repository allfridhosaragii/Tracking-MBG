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

        $closureService = app(\App\Services\TripClosureService::class);
        $closureReadiness = $closureService->getClosureReadiness($trip);

        $stops = $trip->stops->sortBy('sequence_number')->values();
        $activeStop = $closureReadiness['all_stops_handled']
            ? null
            : $stops->first(function ($stop) {
                return in_array($stop->status, [
                    \App\Enums\TripStopStatus::EN_ROUTE,
                    \App\Enums\TripStopStatus::ARRIVED,
                    \App\Enums\TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
                    \App\Enums\TripStopStatus::PENDING,
                ], true);
            });

        return view('driver.trips.active', [
            'trip' => $trip,
            'stops' => $stops,
            'activeStop' => $activeStop,
            'trackingStatus' => $trackingStatus,
            'closureReadiness' => $closureReadiness,
        ]);
    }

    /**
     * Start a new authenticated GPS telemetry session with server-authoritative session UUID (Gate A).
     */
    public function startTelemetry(Request $request, Trip $trip): \Illuminate\Http\JsonResponse
    {
        Gate::authorize('recordTelemetry', $trip);

        $trackingService = app(\App\Services\GpsTrackingService::class);
        $sessionData = $trackingService->startDriverTracking($trip, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Sesi pelacakan GPS aktif berhasil diterbitkan oleh server.',
            'session_id' => $sessionData['session_id'],
            'tracking_status' => $sessionData['tracking_status'],
            'started_at' => $sessionData['started_at'],
        ], 200);
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
        $isStaleAfterStop = $refreshedTrip->tracking_status === 'SHARING_STOPPED'
            || ($refreshedTrip->active_tracking_session_id !== null && (string) $request->input('session_id') !== (string) $refreshedTrip->active_tracking_session_id);

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
     * Supports server-authoritative session UUID and safe stopped_at timestamp to prevent race conditions.
     */
    public function stopTelemetry(Request $request, Trip $trip): \Illuminate\Http\JsonResponse
    {
        Gate::authorize('recordTelemetry', $trip);

        $trackingService = app(\App\Services\GpsTrackingService::class);
        $stoppedAt = $request->input('stopped_at');
        $sessionId = $request->input('session_id');

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

    /**
     * Driver handover workspace at school destination (Page D04).
     */
    public function handover(Request $request, Trip $trip, \App\Models\TripStop $stop): \Illuminate\View\View
    {
        Gate::authorize('view', $trip);

        if ((int) $trip->driver_id !== (int) $request->user()->id) {
            abort(403, 'Anda bukan kurir yang ditugaskan untuk pemberhentian ini.');
        }

        $stop->loadMissing([
            'trip.vehicle',
            'school',
            'deliveryItems.foodBatch.menu',
            'schoolServiceRecord',
            'evidencePhotos',
        ]);

        return view('driver.trips.handover', [
            'stop' => $stop,
            'trip' => $trip,
            'school' => $stop->school,
            'serviceRecord' => $stop->schoolServiceRecord,
        ]);
    }

    /**
     * Driver uploads photo evidence during handover at school stop (Page D04).
     */
    public function handoverEvidence(Request $request, Trip $trip, \App\Models\TripStop $stop): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        Gate::authorize('view', $trip);

        $request->validate([
            'photo' => ['required', 'file', 'image', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $handoverService = app(\App\Services\SchoolHandoverService::class);
        $handoverService->markStopArrival($stop, $request->user(), $request->file('photo'), $request->input('notes'));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Bukti serah terima berhasil diunggah.',
                'stop_status' => $stop->fresh()->status->value,
            ]);
        }

        return back()->with('success', 'Bukti serah terima berhasil diunggah.');
    }

    /**
     * Kurir starts return journey towards SPPG depot (Gate 3).
     */
    public function returnToDepot(Request $request, Trip $trip): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        Gate::authorize('returnToDepot', $trip);

        $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
            'return_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $closureService = app(\App\Services\TripClosureService::class);
        $returnNotes = $request->input('return_notes') ?? $request->input('notes');
        $updatedTrip = $closureService->initiateReturnToDepot($trip, $request->user(), $returnNotes);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Perjalanan pengantaran telah selesai. Memulai rute kembali ke Dapur SPPG.',
                'trip_status' => $updatedTrip->status->value,
                'return_started_at' => $updatedTrip->return_started_at?->toIso8601String(),
            ]);
        }

        return redirect()
            ->route('driver.trips.active', $trip)
            ->with('success', 'Perjalanan pengantaran telah selesai. Memulai rute kembali ke Dapur SPPG.');
    }

    /**
     * Mark arrival of the fleet at SPPG depot (Gate 3).
     */
    public function arriveDepot(Request $request, Trip $trip): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        Gate::authorize('arriveDepot', $trip);

        $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $closureService = app(\App\Services\TripClosureService::class);
        $updatedTrip = $closureService->recordDepotArrival($trip, $request->user(), $request->input('notes'));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Armada telah tiba kembali di Dapur SPPG.',
                'trip_status' => $updatedTrip->status->value,
                'arrived_at_sppg_at' => $updatedTrip->arrived_at_sppg_at?->toIso8601String(),
            ]);
        }

        return redirect()
            ->route('driver.trips.active', $trip)
            ->with('success', 'Armada telah tiba kembali di Dapur SPPG.');
    }

    /**
     * Record tray / ompreng return reconciliation (Gate 4).
     */
    public function reconcileTrays(Request $request, Trip $trip): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        Gate::authorize('reconcileTrays', $trip);

        $request->validate([
            'trays_returned' => ['nullable', 'integer', 'min:0'],
            'total_trays_returned' => ['nullable', 'integer', 'min:0'],
            'trays_damaged' => ['nullable', 'integer', 'min:0'],
            'total_trays_damaged' => ['nullable', 'integer', 'min:0'],
            'trays_missing' => ['nullable', 'integer', 'min:0'],
            'total_trays_missing' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'tray_reconciliation_notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($request->input('trays_returned') === null && $request->input('total_trays_returned') === null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'trays_returned' => ['Jumlah wadah fisik yang kembali wajib diisi (minimal 0).'],
            ]);
        }

        $closureService = app(\App\Services\TripClosureService::class);
        $updatedTrip = $closureService->reconcileTrays($trip, $request->user(), $request->all());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Rekonsiliasi pengembalian wadah/ompreng berhasil dicatat.',
                'total_trays_returned' => $updatedTrip->total_trays_returned,
                'total_trays_damaged' => $updatedTrip->total_trays_damaged,
                'total_trays_missing' => $updatedTrip->total_trays_missing,
            ]);
        }

        return redirect()
            ->route('driver.trips.active', $trip)
            ->with('success', 'Rekonsiliasi pengembalian wadah/ompreng berhasil dicatat.');
    }

    /**
     * Finalize trip closure and administrative sign-off (Gate 5 & Gate 6).
     */
    public function completeTrip(Request $request, Trip $trip): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        Gate::authorize('closeTrip', $trip);

        $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
            'completion_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $closureService = app(\App\Services\TripClosureService::class);
        $completionNotes = $request->input('completion_notes') ?? $request->input('notes');
        $closedTrip = $closureService->closeTrip($trip, $request->user(), $completionNotes);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Perjalanan telah resmi diselesaikan dan ditutup.',
                'trip_status' => $closedTrip->status->value,
                'completed_at' => $closedTrip->completed_at?->toIso8601String(),
                'tracking_status' => $closedTrip->tracking_status,
            ]);
        }

        return redirect()
            ->route('driver.trips.active', $trip)
            ->with('success', 'Perjalanan telah resmi diselesaikan dan ditutup.');
    }
}


