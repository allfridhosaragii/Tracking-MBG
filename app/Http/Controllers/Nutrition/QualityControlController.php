<?php

namespace App\Http\Controllers\Nutrition;

use App\Http\Controllers\Controller;
use App\Http\Requests\PerformQualityCheckRequest;
use App\Models\FoodBatch;
use App\Services\QualityControlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QualityControlController extends Controller
{
    public function __construct(
        protected QualityControlService $qcService
    ) {}

    /**
     * Display QC Workspace (Page G03) listing pending inspection queue and history.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $dateFilter = $request->query('date');

        $pendingBatches = $this->qcService->getPendingBatchesForQc($user->sppg_id);
        $monitoringBatches = $this->qcService->getQcMonitoringList($user->sppg_id, $dateFilter);

        return view('nutrition.qc.index', [
            'pendingBatches' => $pendingBatches,
            'monitoringBatches' => $monitoringBatches,
            'selectedDate' => $dateFilter,
        ]);
    }

    /**
     * Show detail and inspection form for a specific food batch.
     */
    public function show(Request $request, FoodBatch $batch): View
    {
        $user = $request->user();

        if ((int) $user->sppg_id !== (int) $batch->productionPlan->sppg_id) {
            abort(403, 'Akses inspeksi batch lintas SPPG ditolak.');
        }

        $batch->load([
            'productionPlan.sppg',
            'productionPlan.menu.items',
            'productionPlan.allocations.school',
            'stageLogs.updater',
            'qualityChecks.inspector',
            'evidencePhotos.uploader',
            'creator',
            'releaser',
        ]);

        return view('nutrition.qc.show', [
            'batch' => $batch,
            'plan' => $batch->productionPlan,
            'menu' => $batch->productionPlan->menu,
            'qualityChecks' => $batch->qualityChecks,
            'photos' => $batch->evidencePhotos,
        ]);
    }

    /**
     * Submit quality control inspection decision.
     */
    public function storeCheck(PerformQualityCheckRequest $request, FoodBatch $batch): RedirectResponse
    {
        $qc = $this->qcService->recordQualityCheck($batch, $request->validated(), $request->user());

        $statusText = $qc->isSafe()
            ? 'Lolos QC dan siap didistribusikan'
            : 'Ditahan (On Hold) karena tidak memenuhi standar keamanan pangan';

        return redirect()->route('nutrition.qc.index')
            ->with('success', "Pemeriksaan mutu batch {$batch->batch_code} berhasil disimpan: {$statusText}.");
    }
}
