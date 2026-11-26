<?php

namespace App\Http\Controllers\School;

use App\Enums\SchoolReceiptStatus;
use App\Enums\TrayReturnStatus;
use App\Enums\TripStopStatus;
use App\Http\Controllers\Controller;
use App\Models\TrayReturn;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the PIC Sekolah Receipt Workflow dashboard.
     * Strictly scoped to the authenticated user's assigned school.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $school = $user->school;

        if (! $school) {
            abort(403, 'Akun Anda belum dikaitkan dengan unit sekolah penerima MBG.');
        }

        $todayPortionRequest = $school->portionRequests()
            ->whereDate('service_date', '>=', now()->toDateString())
            ->orderBy('service_date')
            ->first();

        $awaitingReceiptsCount = $school->tripStops()
            ->whereIn('status', [
                TripStopStatus::ARRIVED,
                TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
            ])
            ->count();

        $activeDistributionsCount = $school->tripStops()
            ->whereHas('schoolServiceRecord', function ($query) {
                $query->whereIn('status', [
                    SchoolReceiptStatus::RECEIVED,
                    SchoolReceiptStatus::RECEIVED_WITH_DISCREPANCY,
                    SchoolReceiptStatus::CLEARED_TO_SERVE,
                    SchoolReceiptStatus::DISTRIBUTED,
                ]);
            })
            ->count();

        $inUseTraysCount = TrayReturn::whereHas('schoolServiceRecord.tripStop', function ($query) use ($school) {
            $query->where('school_id', $school->id);
        })
            ->where('status', TrayReturnStatus::IN_USE)
            ->count();

        return view('dashboard.school', [
            'user' => $user,
            'school' => $school,
            'sppg' => $school->sppg,
            'todayPortionRequest' => $todayPortionRequest,
            'awaitingReceiptsCount' => $awaitingReceiptsCount,
            'activeDistributionsCount' => $activeDistributionsCount,
            'inUseTraysCount' => $inUseTraysCount,
        ]);
    }
}
