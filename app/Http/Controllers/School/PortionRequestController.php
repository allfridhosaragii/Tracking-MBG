<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelPortionRequest;
use App\Http\Requests\ConfirmPortionRequest;
use App\Http\Requests\RevisePortionRequest;
use App\Http\Requests\SavePortionDraftRequest;
use App\Models\DailyPortionRequest;
use App\Services\PortionRequestService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PortionRequestController extends Controller
{
    /**
     * Display the portion confirmation workspace (Page P02) for the authenticated school PIC.
     */
    public function index(Request $request, PortionRequestService $service): View
    {
        $user = $request->user();
        $school = $user->school;

        if (! $school) {
            abort(403, 'Akun Anda belum dikaitkan dengan unit sekolah penerima MBG.');
        }

        // Default selected service date: tomorrow (H+1) or user selected date
        $nowWib = Carbon::now('Asia/Jakarta');
        $defaultDate = $nowWib->copy()->addDay()->format('Y-m-d');
        $selectedDate = $request->input('date', $defaultDate);

        // Validate date format safely
        try {
            $parsedDate = Carbon::parse($selectedDate, 'Asia/Jakarta')->startOfDay();
            $formattedDate = $parsedDate->format('Y-m-d');
        } catch (\Exception) {
            $formattedDate = $defaultDate;
            $parsedDate = Carbon::parse($formattedDate, 'Asia/Jakarta')->startOfDay();
        }

        // Get or initialize draft for this date
        $portionRequest = $service->getOrCreateForDate($school, $formattedDate, $user);

        // Load 10 recent historical requests
        $history = DailyPortionRequest::where('school_id', $school->id)
            ->with('confirmedBy')
            ->orderByDesc('service_date')
            ->take(10)
            ->get();

        $cutoff = $portionRequest->cutoffDateTime();
        $isPastCutoff = $portionRequest->isPastCutoff($nowWib);

        return view('school.portions.index', [
            'user' => $user,
            'school' => $school,
            'sppg' => $school->sppg,
            'portionRequest' => $portionRequest,
            'selectedDate' => $formattedDate,
            'parsedDate' => $parsedDate,
            'cutoff' => $cutoff,
            'isPastCutoff' => $isPastCutoff,
            'history' => $history,
        ]);
    }

    /**
     * Save draft numbers before formal confirmation.
     */
    public function saveDraft(SavePortionDraftRequest $request, PortionRequestService $service): RedirectResponse
    {
        $user = $request->user();
        $school = $user->school;

        try {
            $portionRequest = $service->saveDraft(
                $school,
                $request->input('service_date'),
                $request->validated(),
                $user
            );

            return redirect()
                ->route('school.portions.index', ['date' => $portionRequest->service_date->format('Y-m-d')])
                ->with('status', 'Draft kebutuhan porsi untuk tanggal ' . $portionRequest->service_date->translatedFormat('d F Y') . ' berhasil disimpan.');
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors())->with('error', collect($e->errors())->flatten()->first() ?? 'Terjadi kesalahan saat menyimpan draft.');
        }
    }

    /**
     * Confirm the portion request for service delivery.
     */
    public function confirm(ConfirmPortionRequest $request, DailyPortionRequest $portionRequest, PortionRequestService $service): RedirectResponse
    {
        Gate::authorize('confirm', $portionRequest);

        try {
            $confirmed = $service->confirmRequest($portionRequest, $request->validated(), $request->user());

            return redirect()
                ->route('school.portions.index', ['date' => $confirmed->service_date->format('Y-m-d')])
                ->with('status', 'Kebutuhan ' . number_format($confirmed->requested_portions) . ' porsi untuk tanggal ' . $confirmed->service_date->translatedFormat('d F Y') . ' berhasil dikonfirmasi secara resmi.');
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors())->with('error', collect($e->errors())->flatten()->first() ?? 'Konfirmasi kebutuhan porsi gagal divalidasi.');
        }
    }

    /**
     * Submit an authorized revision for a confirmed or locked portion request.
     */
    public function revise(RevisePortionRequest $request, DailyPortionRequest $portionRequest, PortionRequestService $service): RedirectResponse
    {
        Gate::authorize('revise', $portionRequest);

        try {
            $revised = $service->submitRevision($portionRequest, $request->validated(), $request->user());

            return redirect()
                ->route('school.portions.index', ['date' => $revised->service_date->format('Y-m-d')])
                ->with('status', 'Revisi kebutuhan porsi menjadi ' . number_format($revised->requested_portions) . ' porsi berhasil diajukan dan dicatat pada audit log.');
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors())->with('error', collect($e->errors())->flatten()->first() ?? 'Revisi gagal divalidasi.');
        }
    }

    /**
     * Cancel meal service for a specific date with justification.
     */
    public function cancel(CancelPortionRequest $request, DailyPortionRequest $portionRequest, PortionRequestService $service): RedirectResponse
    {
        Gate::authorize('cancel', $portionRequest);

        try {
            $cancelled = $service->cancelRequest($portionRequest, $request->input('reason'), $request->user());

            return redirect()
                ->route('school.portions.index', ['date' => $cancelled->service_date->format('Y-m-d')])
                ->with('status', 'Layanan MBG untuk tanggal ' . $cancelled->service_date->translatedFormat('d F Y') . ' telah berstatus Dibatalkan.');
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors())->with('error', collect($e->errors())->flatten()->first() ?? 'Pembatalan gagal diproses.');
        }
    }
}
