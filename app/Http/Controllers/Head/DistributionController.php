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
}
