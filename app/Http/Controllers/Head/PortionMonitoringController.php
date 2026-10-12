<?php

namespace App\Http\Controllers\Head;

use App\Http\Controllers\Controller;
use App\Services\PortionRequestService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortionMonitoringController extends Controller
{
    /**
     * Display the operational portion confirmation monitoring ledger for Kepala SPPG.
     */
    public function index(Request $request, PortionRequestService $service): View
    {
        $user = $request->user();
        $sppg = $user->sppg;

        $nowWib = Carbon::now('Asia/Jakarta');
        $defaultDate = $nowWib->copy()->addDay()->format('Y-m-d');
        $selectedDate = $request->input('date', $defaultDate);

        try {
            $parsedDate = Carbon::parse($selectedDate, 'Asia/Jakarta')->startOfDay();
            $formattedDate = $parsedDate->format('Y-m-d');
        } catch (\Exception) {
            $formattedDate = $defaultDate;
            $parsedDate = Carbon::parse($formattedDate, 'Asia/Jakarta')->startOfDay();
        }

        $aggregate = $service->getSppgAggregateForDate((int) $user->sppg_id, $formattedDate);

        return view('head.portions.index', [
            'user' => $user,
            'sppg' => $sppg,
            'selectedDate' => $formattedDate,
            'parsedDate' => $parsedDate,
            'aggregate' => $aggregate,
        ]);
    }
}
