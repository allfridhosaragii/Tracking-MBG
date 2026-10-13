<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\ProductionPlanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ProductionPlanService $productionPlanService
    ) {}

    /**
     * Display the Asisten Lapangan Production Runbook dashboard (PBI-05 read-only integration).
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $sppg = $user->sppg;
        $sppgId = (int) $user->getEffectiveSppgId();

        $selectedDate = $request->query('date', Carbon::today('Asia/Jakarta')->format('Y-m-d'));
        $parsedDate = Carbon::parse($selectedDate, 'Asia/Jakarta');

        $schools = School::where('sppg_id', $sppgId)
            ->where('is_active', true)
            ->get();

        // Retrieve finalized plans for today/selected date
        $finalizedPlans = $this->productionPlanService->getFinalizedPlansForAssistant($sppgId, $selectedDate);
        $currentPlan = $finalizedPlans->first();

        $targetPortions = $currentPlan ? $currentPlan->totalAllocatedPortions() : 0;

        return view('dashboard.production', [
            'user' => $user,
            'sppg' => $sppg,
            'schools' => $schools,
            'selectedDate' => $selectedDate,
            'parsedDate' => $parsedDate,
            'finalizedPlans' => $finalizedPlans,
            'currentPlan' => $currentPlan,
            'targetPortions' => $targetPortions,
        ]);
    }
}
