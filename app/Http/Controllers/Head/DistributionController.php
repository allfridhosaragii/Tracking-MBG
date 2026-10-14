<?php

namespace App\Http\Controllers\Head;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTripRequest;
use App\Models\Trip;
use App\Services\TripDispatchService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DistributionController extends Controller
{
    public function __construct(
        protected TripDispatchService $tripDispatchService
    ) {}

    /**
     * Display the distribution control workspace (Page K05).
     */
    public function index(Request $request): View
    {
        $actor = $request->user();
        $serviceDate = $request->query('service_date', Carbon::today()->toDateString());

        $data = $this->tripDispatchService->getDispatchOverview((int) $actor->sppg_id, $serviceDate);

        return view('head.distribution.index', $data);
    }

    /**
     * Store and assign a new delivery trip with stops and manifests.
     */
    public function storeTrip(CreateTripRequest $request): RedirectResponse
    {
        $actor = $request->user();
        Gate::authorize('create', Trip::class);

        $trip = $this->tripDispatchService->createTrip($request->validated(), $actor);

        return redirect()
            ->route('head.distribution.index', ['service_date' => $trip->service_date->toDateString()])
            ->with('success', "Penugasan perjalanan {$trip->trip_code} berhasil dibuat dan ditugaskan kepada kurir {$trip->driver->name}.");
    }

    /**
     * Cancel an unstarted trip.
     */
    public function cancelTrip(Request $request, Trip $trip): RedirectResponse
    {
        Gate::authorize('cancel', $trip);

        $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $this->tripDispatchService->cancelTrip($trip, $request->input('reason'), $request->user());

        return redirect()
            ->route('head.distribution.index', ['service_date' => $trip->service_date->toDateString()])
            ->with('success', "Perjalanan {$trip->trip_code} telah dibatalkan dan alokasi makanan dikembalikan ke status siap muat.");
    }

    /**
     * Verify physical loading for a trip (Pre-departure Gate).
     */
    public function verifyLoading(\App\Http\Requests\VerifyLoadingRequest $request, Trip $trip): RedirectResponse
    {
        Gate::authorize('verifyLoading', $trip);

        $this->tripDispatchService->verifyLoading($trip, $request->validated(), $request->user());

        return redirect()
            ->back()
            ->with('success', "Kuantitas fisik muatan perjalanan {$trip->trip_code} berhasil diverifikasi.");
    }

    /**
     * Authorize pre-departure dispatch for a trip (Kepala SPPG Gate).
     */
    public function approveDispatch(\App\Http\Requests\ApproveDispatchRequest $request, Trip $trip): RedirectResponse
    {
        Gate::authorize('approveDispatch', $trip);

        $this->tripDispatchService->approveDispatch($trip, $request->user(), $request->input('notes'));

        return redirect()
            ->back()
            ->with('success', "Otorisasi pengiriman {$trip->trip_code} berhasil diterbitkan. Status perjalanan kini SIAP BERANGKAT (READY).");
    }

    /**
     * Administratively close a trip (Kepala SPPG Gate 5).
     */
    public function closeTrip(Request $request, Trip $trip): RedirectResponse
    {
        Gate::authorize('closeTrip', $trip);

        $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
            'completion_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $closureService = app(\App\Services\TripClosureService::class);
        $closureNotes = $request->input('completion_notes') ?? $request->input('notes');
        $closureService->closeTrip($trip, $request->user(), $closureNotes);

        return redirect()
            ->route('trips.show', $trip)
            ->with('success', "Perjalanan {$trip->trip_code} telah resmi ditutup secara administratif.");
    }

    /**
     * Assign a courier / trip stop to pick up ready containers (Page K05).
     */
    public function assignPickup(Request $request, \App\Models\TrayReturn $trayReturn): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('assignPickup', $trayReturn);

        $request->validate([
            'pickup_trip_stop_id' => 'nullable|integer|exists:trip_stops,id',
            'trip_stop_id' => 'nullable|integer|exists:trip_stops,id',
            'trip_id' => 'nullable|integer|exists:trips,id',
        ]);

        $stopId = $request->input('pickup_trip_stop_id') ?? $request->input('trip_stop_id');
        if (!$stopId && $request->filled('trip_id')) {
            $stop = \App\Models\TripStop::where('trip_id', $request->input('trip_id'))
                ->where('school_id', $trayReturn->school_id)
                ->first();

            if (!$stop) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'trip_id' => ['Rute perjalanan yang dipilih tidak memiliki jadwal pemberhentian di sekolah ini.'],
                ]);
            }
        } elseif ($stopId) {
            $stop = \App\Models\TripStop::findOrFail($stopId);
        } else {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'stop' => ['Titik pemberhentian atau armada perjalanan wajib dipilih untuk penugasan penjemputan.'],
            ]);
        }

        $trayService = app(\App\Services\TrayReturnService::class);
        $trayService->assignPickup($trayReturn, $request->user(), $stop);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Penugasan penjemputan ompreng berhasil ditetapkan.',
                'data' => $trayReturn->fresh(['pickupTripStop.trip.driver']),
            ]);
        }

        return redirect()->back()->with('success', 'Penugasan penjemputan wadah berhasil ditetapkan.');
    }
}
