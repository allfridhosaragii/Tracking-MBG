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
     * Start the trip dispatch.
     */
    public function start(Request $request, Trip $trip): RedirectResponse
    {
        Gate::authorize('start', $trip);

        $this->tripDispatchService->startTrip($trip, $request->user());

        return redirect()
            ->route('driver.trips.prepare', $trip)
            ->with('success', 'Perjalanan pengantaran telah resmi dimulai. Menuju pemberhentian pertama.');
    }
}
