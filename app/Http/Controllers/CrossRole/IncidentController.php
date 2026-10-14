<?php

namespace App\Http\Controllers\CrossRole;

use App\Enums\IncidentCategory;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Incident;
use App\Services\IncidentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class IncidentController extends Controller
{
    /**
     * Display a scoped list of operational incidents (Page C01).
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = Incident::query()->with(['sppg', 'school', 'reporter', 'resolver']);

        // Scope enforcement
        if ($user->role === UserRole::PIC_SEKOLAH) {
            $query->where(function ($q) use ($user) {
                $q->where('school_id', $user->school_id)
                  ->orWhere('reported_by', $user->id);
            });
        } else {
            $query->where('sppg_id', $user->sppg_id);
        }

        // Optional filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        $incidents = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('incidents.index', [
            'incidents' => $incidents,
            'user' => $user,
            'categories' => IncidentCategory::cases(),
            'severities' => IncidentSeverity::cases(),
            'statuses' => IncidentStatus::cases(),
        ]);
    }

    /**
     * Display the full detail and audit history of an incident (Page C01).
     */
    public function show(Request $request, Incident $incident): View
    {
        Gate::authorize('view', $incident);

        $incident->loadMissing([
            'sppg',
            'school',
            'reporter',
            'resolver',
            'evidencePhotos',
        ]);

        $auditLogs = ActivityLog::where('auditable_type', Incident::class)
            ->where('auditable_id', $incident->id)
            ->with('actor')
            ->orderByDesc('created_at')
            ->get();

        return view('incidents.show', [
            'incident' => $incident,
            'auditLogs' => $auditLogs,
            'user' => $request->user(),
        ]);
    }

    /**
     * Report a new incident.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        Gate::authorize('create', Incident::class);

        $request->validate([
            'category' => 'required|string',
            'severity' => 'required|string',
            'description' => 'required|string|max:2000',
            'school_id' => 'nullable|integer',
            'subject_type' => 'nullable|string',
            'subject_id' => 'nullable|integer',
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $service = app(IncidentService::class);
        $incident = $service->createIncident(
            $request->only(['category', 'severity', 'description', 'school_id', 'subject_type', 'subject_id']),
            $request->user(),
            $request->file('photo')
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Laporan insiden berhasil dicatat.',
                'data' => $incident,
            ]);
        }

        return redirect()->route('incidents.show', $incident)->with('success', 'Laporan kendala berhasil dikirim.');
    }

    /**
     * Transition the lifecycle status of an incident.
     */
    public function transition(Request $request, Incident $incident, ?string $action = null): JsonResponse|RedirectResponse
    {
        $action = $action ?? $request->input('action');
        $reopenReason = $request->input('reopen_reason') ?? $request->input('reason');
        $request->merge([
            'action' => $action,
            'reopen_reason' => $reopenReason,
        ]);

        $request->validate([
            'action' => 'required|string|in:acknowledge,start_progress,resolve,close,reopen',
            'notes' => 'nullable|string|max:2000',
            'resolution_notes' => 'required_if:action,resolve|nullable|string|max:2000',
            'reopen_reason' => 'required_if:action,reopen|nullable|string|max:2000',
        ]);

        $service = app(IncidentService::class);
        $user = $request->user();

        switch ($action) {
            case 'acknowledge':
                Gate::authorize('acknowledge', $incident);
                $service->acknowledge($incident, $user, $request->input('notes'));
                $message = 'Laporan insiden berhasil diterima (ACKNOWLEDGED).';
                break;

            case 'start_progress':
                Gate::authorize('startProgress', $incident);
                $service->startProgress($incident, $user, $request->input('notes'));
                $message = 'Status insiden dialihkan ke Dalam Penanganan (IN_PROGRESS).';
                break;

            case 'resolve':
                Gate::authorize('resolve', $incident);
                $service->resolve($incident, $user, (string) $request->input('resolution_notes'));
                $message = 'Insiden berhasil diselesaikan dengan catatan tindakan korektif (RESOLVED).';
                break;

            case 'close':
                Gate::authorize('close', $incident);
                $service->close($incident, $user, $request->input('notes'));
                $message = 'Insiden telah resmi disahkan dan ditutup permanen (CLOSED).';
                break;

            case 'reopen':
                Gate::authorize('reopen', $incident);
                $service->reopen($incident, $user, (string) $request->input('reopen_reason'));
                $message = 'Insiden telah dibuka kembali untuk peninjauan lanjutan (REOPENED).';
                break;

            default:
                abort(400, 'Aksi transisi tidak valid.');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $incident->fresh(),
            ]);
        }

        return redirect()->route('incidents.show', $incident)->with('success', $message);
    }
}
