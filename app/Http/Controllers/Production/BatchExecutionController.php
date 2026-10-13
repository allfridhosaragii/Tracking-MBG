<?php

namespace App\Http\Controllers\Production;

use App\Enums\ProductionStage;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdvanceBatchStageRequest;
use App\Http\Requests\CreateFoodBatchRequest;
use App\Http\Requests\RecordActualPortionsRequest;
use App\Http\Requests\SubmitBatchToQcRequest;
use App\Models\FoodBatch;
use App\Models\ProductionPlan;
use App\Services\FoodBatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BatchExecutionController extends Controller
{
    public function __construct(
        protected FoodBatchService $batchService
    ) {}

    /**
     * Display Kitchen Execution Workspace (Page A02) for Asisten Lapangan.
     */
    public function showExecutionWorkspace(Request $request, ProductionPlan $plan): View
    {
        $user = $request->user();

        // Tenant guard
        if ((int) $user->sppg_id !== (int) $plan->sppg_id) {
            abort(403, 'Akses rencana produksi lintas SPPG ditolak.');
        }

        $plan->load([
            'sppg',
            'menu.items',
            'allocations.school',
            'foodBatches' => function ($q) {
                $q->with(['stageLogs', 'qualityChecks.inspector', 'evidencePhotos', 'creator', 'releaser'])
                    ->orderBy('id', 'asc');
            },
            'finalizer',
        ]);

        return view('production.execution.index', [
            'plan' => $plan,
            'batches' => $plan->foodBatches,
            'totalPlanned' => $plan->totalAllocatedPortions(),
            'totalActual' => $plan->totalActualPortions(),
            'totalReleased' => $plan->totalReleasedPortions(),
        ]);
    }

    /**
     * Create a new food batch from the plan.
     */
    public function storeBatch(CreateFoodBatchRequest $request, ProductionPlan $plan): RedirectResponse
    {
        $batch = $this->batchService->createBatch($plan, $request->validated(), $request->user());

        return redirect()->route('production.plans.execute', $plan)
            ->with('success', "Batch {$batch->batch_code} berhasil dibuat dengan target {$batch->planned_portions} porsi.");
    }

    /**
     * Advance a batch to the next production stage.
     */
    public function advanceStage(AdvanceBatchStageRequest $request, FoodBatch $batch): RedirectResponse
    {
        $targetStage = ProductionStage::from($request->validated()['stage']);
        $this->batchService->advanceStage($batch, $targetStage, $request->validated()['notes'] ?? null, $request->user());

        return redirect()->route('production.plans.execute', $batch->production_plan_id)
            ->with('success', "Tahapan batch {$batch->batch_code} berhasil diperbarui ke {$targetStage->label()}.");
    }

    /**
     * Record actual produced portions for a batch.
     */
    public function recordActual(RecordActualPortionsRequest $request, FoodBatch $batch): RedirectResponse
    {
        $actual = (int) $request->validated()['actual_portions'];
        $this->batchService->recordActualPortions($batch, $actual, $request->validated()['notes'] ?? null, $request->user());

        $diff = $actual - $batch->planned_portions;
        $diffText = $diff === 0 ? 'tepat sesuai target' : ($diff > 0 ? "+{$diff} porsi lebih" : "{$diff} porsi kurang");

        return redirect()->route('production.plans.execute', $batch->production_plan_id)
            ->with('success', "Realisasi {$actual} porsi berhasil disimpan ({$diffText}).");
    }

    /**
     * Submit batch to Quality Control inspection.
     */
    public function submitToQc(SubmitBatchToQcRequest $request, FoodBatch $batch): RedirectResponse
    {
        $this->batchService->submitToQc(
            $batch,
            $request->file('photo'),
            $request->validated()['notes'] ?? null,
            $request->user(),
            $request->validated()
        );

        return redirect()->route('production.plans.execute', $batch->production_plan_id)
            ->with('success', "Batch {$batch->batch_code} berhasil diserahkan ke Pengawas Gizi untuk Pemeriksaan Mutu (QC).");
    }
}
