<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmSchoolReceiptRequest;
use App\Models\TripStop;
use App\Services\SchoolHandoverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SchoolReceiptController extends Controller
{
    /**
     * Display a list of active and recent deliveries for the authenticated PIC's school (Page P04).
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $stops = TripStop::where('school_id', $schoolId)
            ->with([
                'trip.driver',
                'trip.vehicle',
                'deliveryItems.foodBatch.menu',
                'schoolServiceRecord',
                'qualityChecks',
            ])
            ->join('trips', 'trips.id', '=', 'trip_stops.trip_id')
            ->orderByDesc('trips.service_date')
            ->orderBy('trip_stops.sequence_number')
            ->select('trip_stops.*')
            ->paginate(15);

        return view('school.receipts.index', [
            'stops' => $stops,
            'school' => $user->school,
        ]);
    }

    /**
     * Display the official receipt inspection & confirmation workspace for a delivery stop (Page P04).
     */
    public function show(Request $request, TripStop $stop): View
    {
        Gate::authorize('confirmReceipt', $stop);

        $stop->loadMissing([
            'trip.driver',
            'trip.vehicle',
            'school',
            'deliveryItems.foodBatch.menu',
            'schoolServiceRecord.receiver',
            'schoolServiceRecord.evidencePhotos',
            'qualityChecks.inspector',
            'evidencePhotos',
        ]);

        return view('school.receipts.show', [
            'stop' => $stop,
            'trip' => $stop->trip,
            'school' => $stop->school,
            'serviceRecord' => $stop->schoolServiceRecord,
            'primaryBatch' => $stop->deliveryItems->first()?->foodBatch,
            'primaryMenu' => $stop->deliveryItems->first()?->foodBatch?->menu,
        ]);
    }

    /**
     * Submit physical portion count, organoleptic arrival check, and digital receipt signature.
     */
    public function confirm(ConfirmSchoolReceiptRequest $request, TripStop $stop): JsonResponse|RedirectResponse
    {
        $handoverService = app(SchoolHandoverService::class);
        $serviceRecord = $handoverService->confirmReceipt(
            $stop,
            $request->user(),
            $request->validated(),
            $request->file('evidence_photo')
        );

        $refreshedStop = $stop->fresh(['schoolServiceRecord', 'qualityChecks']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Penerimaan makanan di {$stop->school->name} berhasil diverifikasi.",
                'receipt_status' => $serviceRecord->status->value,
                'stop_status' => $refreshedStop->status->value,
                'expected_portions' => $serviceRecord->expected_portions,
                'received_portions' => $serviceRecord->received_portions,
                'damaged_portions' => $serviceRecord->damaged_portions,
                'shortage_portions' => $serviceRecord->shortage_portions,
                'service_record_id' => $serviceRecord->id,
            ], 200);
        }

        return redirect()
            ->route('school.receipts.show', $stop)
            ->with('success', "Penerimaan makanan di {$stop->school->name} berhasil diverifikasi dan dicatat.");
    }
}
