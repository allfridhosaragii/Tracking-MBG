<?php

namespace App\Http\Controllers\Driver;

use App\Enums\TrayReturnStatus;
use App\Http\Controllers\Controller;
use App\Models\TrayReturn;
use App\Services\TrayReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DriverPickupController extends Controller
{
    /**
     * Display assigned and available container pickup tasks for the courier (Page D05).
     */
    public function index(Request $request): View
    {
        $driver = $request->user();
        $sppgId = $driver->sppg_id;

        // GATE B: Courier only sees tasks officially assigned to them
        $pendingPickups = TrayReturn::whereHas('pickupTripStop.trip', function ($q) use ($driver) {
            $q->where('driver_id', $driver->id);
        })
        ->where('status', TrayReturnStatus::PICKUP_ASSIGNED)
        ->with([
            'schoolServiceRecord.tripStop.school',
            'schoolServiceRecord.tripStop.trip',
            'pickupTripStop.trip',
            'evidencePhotos',
        ])
        ->orderBy('ready_at')
        ->get();

        // Recent pickups completed by this driver
        $completedPickups = TrayReturn::whereHas('pickupTripStop.trip', function ($q) use ($driver) {
            $q->where('driver_id', $driver->id);
        })
        ->whereIn('status', [TrayReturnStatus::PICKED_UP, TrayReturnStatus::RETURNED_TO_SPPG, TrayReturnStatus::DISCREPANCY])
        ->whereNotNull('picked_up_at')
        ->with([
            'schoolServiceRecord.tripStop.school',
            'schoolServiceRecord.tripStop.trip',
            'evidencePhotos',
        ])
        ->orderByDesc('picked_up_at')
        ->paginate(15);

        return view('driver.pickups.index', [
            'pendingPickups' => $pendingPickups,
            'completedPickups' => $completedPickups,
            'driver' => $driver,
        ]);
    }

    /**
     * Submit physical pickup count and evidence for returning containers.
     */
    public function pickup(Request $request, TrayReturn $trayReturn): JsonResponse|RedirectResponse
    {
        Gate::authorize('pickup', $trayReturn);

        $request->validate([
            'picked_up_count' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:1000',
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $service = app(TrayReturnService::class);
        $service->recordPickup(
            $trayReturn,
            $request->user(),
            $request->only(['picked_up_count', 'notes']),
            $request->file('photo')
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengambilan ompreng berhasil dicatat oleh kurir.',
                'data' => $trayReturn->fresh(),
            ]);
        }

        return redirect()->route('driver.pickups.index')->with('success', 'Pengambilan ompreng berhasil dicatat.');
    }
}
