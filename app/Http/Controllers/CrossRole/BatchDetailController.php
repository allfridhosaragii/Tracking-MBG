<?php

namespace App\Http\Controllers\CrossRole;

use App\Http\Controllers\Controller;
use App\Models\FoodBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BatchDetailController extends Controller
{
    /**
     * Display Page C02: Detail dan Jejak Batch (Batch Traceability Workspace).
     * Enforces FoodBatchPolicy authorization for multi-role cross access.
     */
    public function show(Request $request, FoodBatch $batch): View
    {
        Gate::authorize('view', $batch);

        $batch->load([
            'productionPlan.sppg',
            'productionPlan.menu.items',
            'productionPlan.allocations.school',
            'stageLogs.updater',
            'qualityChecks.inspector',
            'evidencePhotos.uploader',
            'creator',
            'releaser',
            'exceptionApprover',
            'deliveryItems.productionAllocation.school',
        ]);

        return view('batches.show', [
            'batch' => $batch,
            'plan' => $batch->productionPlan,
            'menu' => $batch->menu ?? $batch->productionPlan->menu,
            'stageLogs' => $batch->stageLogs()->orderBy('id', 'asc')->get(),
            'qualityChecks' => $batch->qualityChecks()->orderBy('id', 'desc')->get(),
            'photos' => $batch->evidencePhotos,
            'deliveryItems' => $batch->deliveryItems,
        ]);
    }
}
