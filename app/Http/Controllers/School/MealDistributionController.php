<?php

namespace App\Http\Controllers\School;

use App\Enums\QualityCheckConclusion;
use App\Enums\QualityCheckStage;
use App\Enums\SchoolReceiptStatus;
use App\Http\Controllers\Controller;
use App\Models\SchoolServiceRecord;
use App\Services\SchoolHandoverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MealDistributionController extends Controller
{
    /**
     * Display a list of meal services and distribution status for the authenticated PIC's school (Page P05).
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $school = $user->school;

        $serviceRecords = SchoolServiceRecord::whereHas('tripStop', function ($q) use ($user) {
            $q->where('school_id', $user->school_id);
        })
        ->with([
            'tripStop.trip.driver',
            'tripStop.deliveryItems.foodBatch.menu',
            'receiver',
            'qualityChecks',
            'trayReturn',
        ])
        ->orderByDesc('id')
        ->paginate(15);

        $allRecords = SchoolServiceRecord::whereHas('tripStop', function ($q) use ($user) {
            $q->where('school_id', $user->school_id);
        })->get();

        $metrics = [
            'waiting_recheck' => $allRecords->filter(fn ($r) => in_array($r->status, [
                SchoolReceiptStatus::RECEIVED,
                SchoolReceiptStatus::RECEIVED_WITH_DISCREPANCY,
            ], true))->count(),
            'ready_to_serve' => $allRecords->where('status', SchoolReceiptStatus::CLEARED_TO_SERVE)->count(),
            'distributed' => $allRecords->where('status', SchoolReceiptStatus::DISTRIBUTED)->count(),
            'completed' => $allRecords->where('status', SchoolReceiptStatus::MEAL_COMPLETED)->count(),
            'held' => $allRecords->filter(fn ($r) => in_array($r->status, [
                SchoolReceiptStatus::HELD,
                SchoolReceiptStatus::REJECTED,
            ], true))->count(),
        ];

        return view('school.meal-distribution.index', [
            'school' => $school,
            'serviceRecords' => $serviceRecords,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Display the official meal distribution workspace, pre-consumption check form, and meal closing (Page P05).
     */
    public function show(Request $request, SchoolServiceRecord $record): View
    {
        Gate::authorize('view', $record);

        $record->loadMissing([
            'tripStop.trip.driver',
            'tripStop.school',
            'tripStop.deliveryItems.foodBatch.menu',
            'receiver',
            'qualityChecks.inspector',
            'evidencePhotos',
            'trayReturn',
            'incidents',
        ]);

        $stop = $record->tripStop;
        $arrivalQc = $stop->qualityChecks->where('check_stage', QualityCheckStage::ON_ARRIVAL)->sortByDesc('created_at')->first();
        $preConsumptionQc = $stop->qualityChecks->whereIn('check_stage', [
            QualityCheckStage::BEFORE_CONSUMPTION,
            QualityCheckStage::PRE_CONSUMPTION,
        ])->sortByDesc('created_at')->first();

        $usablePortions = max(0, (int) $record->received_portions - (int) $record->damaged_portions);

        return view('school.meal-distribution.show', [
            'record' => $record,
            'stop' => $stop,
            'school' => $stop->school,
            'trip' => $stop->trip,
            'deliveryItems' => $stop->deliveryItems,
            'arrivalQc' => $arrivalQc,
            'preConsumptionQc' => $preConsumptionQc,
            'usablePortions' => $usablePortions,
        ]);
    }

    /**
     * Record pre-consumption quality check (BEFORE_CONSUMPTION / second QC checkpoint).
     */
    public function recordPreConsumptionCheck(Request $request, SchoolServiceRecord $record): JsonResponse|RedirectResponse
    {
        Gate::authorize('manageMeal', $record);

        $validated = $request->validate([
            'appearance_ok' => 'required|boolean',
            'aroma_ok' => 'required|boolean',
            'texture_ok' => 'required|boolean',
            'taste_tested' => 'nullable|boolean',
            'taste_ok' => 'nullable|boolean',
            'taste_skipped_reason' => 'nullable|string|max:500',
            'temperature_c' => 'nullable|numeric',
            'conclusion' => 'nullable|string|in:SAFE,UNSAFE,HOLD',
            'notes' => 'nullable|string|max:1000',
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $handoverService = app(SchoolHandoverService::class);
        $updatedRecord = $handoverService->recordPreConsumptionCheck(
            $record,
            $request->user(),
            $validated,
            $request->file('photo')
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $updatedRecord->status === SchoolReceiptStatus::CLEARED_TO_SERVE
                    ? 'Pemeriksaan sebelum konsumsi berhasil. Makanan dinyatakan aman dan siap dibagikan.'
                    : 'Pemeriksaan sebelum konsumsi menghasilkan kendala keamanan pangan. Layanan makanan ditahan.',
                'status' => $updatedRecord->status->value,
                'record' => $updatedRecord->fresh(['qualityChecks']),
            ]);
        }

        $message = $updatedRecord->status === SchoolReceiptStatus::CLEARED_TO_SERVE
            ? 'Pemeriksaan sebelum konsumsi berhasil: Makanan aman untuk dibagikan.'
            : 'Peringatan: Makanan ditahan dari pembagian karena kendala keamanan pangan.';

        return redirect()->route('school.meal-distribution.show', $record)->with('success', $message);
    }

    /**
     * Mark the start of meal distribution to students.
     */
    public function startDistribution(Request $request, SchoolServiceRecord $record): JsonResponse|RedirectResponse
    {
        Gate::authorize('manageMeal', $record);

        $handoverService = app(SchoolHandoverService::class);
        $updatedRecord = $handoverService->startDistribution($record, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pembagian makanan telah resmi dimulai.',
                'status' => $updatedRecord->status->value,
                'distribution_started_at' => $updatedRecord->distribution_started_at?->toIso8601String(),
            ]);
        }

        return redirect()->route('school.meal-distribution.show', $record)->with('success', 'Pembagian makanan dimulai.');
    }

    /**
     * Record actual served portions, leftovers, and officially close the meal service.
     */
    public function completeMeal(Request $request, SchoolServiceRecord $record): JsonResponse|RedirectResponse
    {
        Gate::authorize('manageMeal', $record);

        $validated = $request->validate([
            'served_portions' => 'required|integer|min:0',
            'leftover_portions' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $handoverService = app(SchoolHandoverService::class);
        $updatedRecord = $handoverService->completeMeal(
            $record,
            $request->user(),
            $validated,
            $request->file('photo')
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kegiatan makan siswa telah resmi selesai dicatat.',
                'status' => $updatedRecord->status->value,
                'served_portions' => $updatedRecord->served_portions,
                'leftover_portions' => $updatedRecord->leftover_portions,
                'meal_completed_at' => $updatedRecord->meal_completed_at?->toIso8601String(),
            ]);
        }

        return redirect()->route('school.meal-distribution.show', $record)->with('success', 'Kegiatan makan berhasil diselesaikan. Wadah ompreng kini siap disiapkan untuk penjemputan.');
    }
}
