<?php

namespace App\Http\Controllers\Production;

use App\Enums\TrayReturnStatus;
use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Models\TrayReturn;
use App\Models\Trip;
use App\Services\TrayReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LogisticsController extends Controller
{
    /**
     * Display the vehicle loading checklist and container return ledger (Page A03).
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $sppgId = $user->sppg_id;

        // Trips preparing or in transit for the SPPG
        $trips = Trip::where('sppg_id', $sppgId)
            ->whereIn('status', [TripStatus::READY, TripStatus::IN_PROGRESS, TripStatus::ASSIGNED])
            ->with(['driver', 'vehicle', 'stops.school', 'stops.deliveryItems.foodBatch'])
            ->latest()
            ->get();

        // Containers picked up by couriers and awaiting depot reception
        $incomingReturns = TrayReturn::whereHas('schoolServiceRecord.tripStop.trip', function ($q) use ($sppgId) {
            $q->where('sppg_id', $sppgId);
        })
        ->where('status', TrayReturnStatus::PICKED_UP)
        ->with([
            'schoolServiceRecord.tripStop.school',
            'schoolServiceRecord.tripStop.trip.driver',
            'pickupTripStop.trip.driver',
            'evidencePhotos',
        ])
        ->orderByDesc('picked_up_at')
        ->get();

        // Completed returns ledger
        $completedReturns = TrayReturn::whereHas('schoolServiceRecord.tripStop.trip', function ($q) use ($sppgId) {
            $q->where('sppg_id', $sppgId);
        })
        ->whereIn('status', [TrayReturnStatus::RETURNED_TO_SPPG, TrayReturnStatus::DISCREPANCY])
        ->with([
            'schoolServiceRecord.tripStop.school',
            'schoolServiceRecord.tripStop.trip.driver',
            'evidencePhotos',
            'incidents',
        ])
        ->orderByDesc('returned_at')
        ->paginate(15);

        return view('production.logistics.index', [
            'trips' => $trips,
            'incomingReturns' => $incomingReturns,
            'completedReturns' => $completedReturns,
            'user' => $user,
        ]);
    }

    /**
     * Reconcile physical container counts returned to SPPG depot.
     */
    public function receive(Request $request, TrayReturn $trayReturn): JsonResponse|RedirectResponse
    {
        Gate::authorize('receive', $trayReturn);

        $request->validate([
            'returned_count' => 'required|integer|min:0',
            'damaged_count' => 'nullable|integer|min:0',
            'missing_count' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $service = app(TrayReturnService::class);
        $service->receiveAtDepot(
            $trayReturn,
            $request->user(),
            $request->only(['returned_count', 'damaged_count', 'missing_count', 'notes']),
            $request->file('photo')
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Penerimaan wadah di SPPG berhasil dicatat.',
                'data' => $trayReturn->fresh(),
            ]);
        }

        return redirect()->route('production.logistics.index')->with('success', 'Penerimaan wadah di depot SPPG berhasil dicatat.');
    }
}
