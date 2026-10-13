<?php

namespace App\Http\Controllers\Head;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProductionPlanRequest;
use App\Http\Requests\FinalizeProductionPlanRequest;
use App\Models\DailyPortionRequest;
use App\Models\ProductionPlan;
use App\Services\PortionRequestService;
use App\Services\ProductionPlanService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionPlanController extends Controller
{
    public function __construct(
        protected ProductionPlanService $productionPlanService,
        protected PortionRequestService $portionRequestService
    ) {}

    /**
     * Display the operational planning workspace (Page K04).
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $sppgId = (int) $user->getEffectiveSppgId();

        $selectedDate = $request->query('date', Carbon::tomorrow('Asia/Jakarta')->format('Y-m-d'));
        $menuId = $request->query('menu_id') ? (int) $request->query('menu_id') : null;

        $workspace = $this->productionPlanService->getPlanningWorkspace($sppgId, $selectedDate, $menuId);

        return view('head.production.index', $workspace);
    }

    /**
     * Create an initial production plan for a specific date and active menu.
     */
    public function store(CreateProductionPlanRequest $request): RedirectResponse
    {
        $user = $request->user();
        $sppgId = (int) $user->getEffectiveSppgId();

        $plan = $this->productionPlanService->createPlan($sppgId, $request->validated(), $user);

        return redirect()->route('head.production.index', [
            'date' => $plan->service_date->format('Y-m-d'),
            'menu_id' => $plan->menu_id,
        ])->with('status', 'Rencana produksi awal berhasil dibuat. Tinjau alokasi sebelum finalisasi.');
    }

    /**
     * Finalize the production plan and commit school allocations.
     */
    public function finalize(FinalizeProductionPlanRequest $request, ProductionPlan $productionPlan): RedirectResponse
    {
        $user = $request->user();

        $this->productionPlanService->finalizePlan(
            $productionPlan,
            $request->validated()['allocations'],
            $user
        );

        return redirect()->route('head.production.index', [
            'date' => $productionPlan->service_date->format('Y-m-d'),
            'menu_id' => $productionPlan->menu_id,
        ])->with('status', 'Rencana produksi resmi difinalisasi. Seluruh alokasi sekolah terkunci (LOCKED) untuk produksi dapur.');
    }

    /**
     * Approve a pending portion revision from a school.
     */
    public function approveRevision(Request $request, DailyPortionRequest $portionRequest): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeAccess($user, $portionRequest);

        $this->portionRequestService->approveRevision($portionRequest, $user);

        return back()->with('status', "Revisi porsi untuk {$portionRequest->school->name} disetujui dan dikunci resmi.");
    }

    /**
     * Reject a pending portion revision from a school.
     */
    public function rejectRevision(Request $request, DailyPortionRequest $portionRequest): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeAccess($user, $portionRequest);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'reason.required' => 'Alasan penolakan revisi wajib disertakan.',
            'reason.min' => 'Alasan penolakan minimal 5 karakter.',
        ]);

        $this->portionRequestService->rejectRevision($portionRequest, $validated['reason'], $user);

        return back()->with('status', "Revisi porsi untuk {$portionRequest->school->name} ditolak. Kebutuhan lama dipertahankan.");
    }

    /**
     * Approve exception for requested portions exceeding registered beneficiaries.
     */
    public function approveException(Request $request, DailyPortionRequest $portionRequest): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeAccess($user, $portionRequest);

        $this->portionRequestService->approveExceedingException($portionRequest, $user);

        return back()->with('status', "Pengecualian kuota siswa untuk {$portionRequest->school->name} disahkan oleh Kepala SPPG.");
    }

    /**
     * Authorize that the actor belongs to the same tenant as the portion request.
     */
    protected function authorizeAccess($user, DailyPortionRequest $portionRequest): void
    {
        if ((int) $user->getEffectiveSppgId() !== (int) $portionRequest->school->sppg_id) {
            abort(403, 'Akses ditolak: Permintaan porsi berada di luar wilayah kerja SPPG Anda.');
        }
    }
}
