<?php

namespace App\Http\Controllers\Nutrition;

use App\Enums\FoodBatchStatus;
use App\Http\Controllers\Controller;
use App\Models\FoodBatch;
use App\Models\Menu;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the Pengawas Gizi Review Desk dashboard.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $sppg = $user->sppg;
        $sppgId = (int) $user->sppg_id;
        $today = Carbon::today('Asia/Jakarta')->format('Y-m-d');

        $schools = School::where('sppg_id', $sppgId)
            ->where('is_active', true)
            ->get();

        $totalBeneficiaries = $schools->sum('registered_beneficiaries');

        $activeMenus = Menu::forSppg($sppgId)
            ->active()
            ->with('items')
            ->orderByDesc('updated_at')
            ->take(5)
            ->get();

        $draftMenusCount = Menu::forSppg($sppgId)
            ->draft()
            ->count();

        $pendingQcBatches = FoodBatch::forSppg($sppgId)
            ->whereIn('status', [FoodBatchStatus::QC_CHECK->value, FoodBatchStatus::ON_HOLD->value])
            ->with('productionPlan.menu')
            ->latest('updated_at')
            ->get();

        $releasedTodayCount = FoodBatch::forSppg($sppgId)
            ->where('status', FoodBatchStatus::RELEASED->value)
            ->whereDate('released_at', $today)
            ->count();

        return view('dashboard.nutrition', [
            'user' => $user,
            'sppg' => $sppg,
            'schools' => $schools,
            'totalBeneficiaries' => $totalBeneficiaries,
            'activeMenus' => $activeMenus,
            'draftMenusCount' => $draftMenusCount,
            'pendingQcBatches' => $pendingQcBatches,
            'releasedTodayCount' => $releasedTodayCount,
        ]);
    }
}
