<?php

namespace App\Http\Controllers\School;

use App\Enums\TrayReturnStatus;
use App\Http\Controllers\Controller;
use App\Models\SchoolServiceRecord;
use App\Models\TrayReturn;
use App\Services\TrayReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TrayReturnController extends Controller
{
    /**
     * Display the container status, ready-to-pickup actions, and history (Page P06).
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $school = $user->school;

        // Auto-initialize TrayReturn records for any completed receipts of this school
        $uninitiatedRecords = SchoolServiceRecord::whereHas('tripStop', function ($q) use ($user) {
            $q->where('school_id', $user->school_id);
        })->doesntHave('trayReturn')->get();

        $trayService = app(TrayReturnService::class);
        foreach ($uninitiatedRecords as $record) {
            $trayService->initiateForRecord($record);
        }

        $trayReturns = TrayReturn::whereHas('schoolServiceRecord.tripStop', function ($q) use ($user) {
            $q->where('school_id', $user->school_id);
        })
        ->with([
            'schoolServiceRecord.tripStop.trip.driver',
            'schoolServiceRecord.tripStop.school',
            'pickupTripStop.trip.driver',
            'evidencePhotos',
            'incidents',
        ])
        ->orderByDesc('id')
        ->paginate(15);

        // Compute summary metrics for the PIC workspace
        $allTrays = TrayReturn::whereHas('schoolServiceRecord.tripStop', function ($q) use ($user) {
            $q->where('school_id', $user->school_id);
        })->get();

        $metrics = [
            'in_use' => $allTrays->where('status', TrayReturnStatus::IN_USE)->count(),
            'ready' => $allTrays->where('status', TrayReturnStatus::READY_FOR_PICKUP)->count(),
            'picked_up' => $allTrays->where('status', TrayReturnStatus::PICKED_UP)->count(),
            'returned' => $allTrays->where('status', TrayReturnStatus::RETURNED_TO_SPPG)->count(),
            'discrepancy' => $allTrays->where('status', TrayReturnStatus::DISCREPANCY)->count(),
        ];

        return view('school.trays.index', [
            'school' => $school,
            'trayReturns' => $trayReturns,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Submit physical ready container count and transition to READY_FOR_PICKUP.
     */
    public function markReady(Request $request, TrayReturn $trayReturn): JsonResponse|RedirectResponse
    {
        Gate::authorize('markReady', $trayReturn);

        $request->validate([
            'ready_count' => 'required|integer|min:0',
            'damaged_count' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $service = app(TrayReturnService::class);
        $service->markReady(
            $trayReturn,
            $request->user(),
            $request->only(['ready_count', 'damaged_count', 'notes']),
            $request->file('photo')
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Ompreng berhasil ditandai siap dijemput oleh kurir.',
                'data' => $trayReturn->fresh(),
            ]);
        }

        return redirect()->route('school.trays.index')->with('success', 'Wadah berhasil ditandai siap diambil.');
    }
}
