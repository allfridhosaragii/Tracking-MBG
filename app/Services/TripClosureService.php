<?php

namespace App\Services;

use App\Enums\IncidentCategory;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Enums\SchoolReceiptStatus;
use App\Enums\TripStatus;
use App\Enums\TripStopStatus;
use App\Models\ActivityLog;
use App\Models\Incident;
use App\Models\SchoolServiceRecord;
use App\Models\TrayReturn;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TripClosureService
{
    public function __construct(
        protected OsrmRoutingService $routingService
    ) {}

    /**
     * Kurir starts return journey from final delivery destination towards SPPG depot (Gate 3).
     *
     * Precondition: Every stop on the trip must have reached a terminal/handled state
     * (HANDED_OVER, COMPLETED, SKIPPED, or EXCEPTION).
     */
    public function initiateReturnToDepot(Trip $trip, User $actor, ?string $notes = null): Trip
    {
        return DB::transaction(function () use ($trip, $actor, $notes) {
            $lockedTrip = Trip::where('id', $trip->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTrip->status === TripStatus::COMPLETED) {
                return $lockedTrip;
            }

            if ($lockedTrip->status !== TripStatus::IN_PROGRESS) {
                throw ValidationException::withMessages([
                    'status' => ['Perjalanan harus berstatus Dalam Perjalanan (IN_PROGRESS) untuk memulai kepulangan armada.'],
                ]);
            }

            // Gate 2: Enforce that all stops are completed or handled before return journey starts
            $unhandledStop = $lockedTrip->stops()
                ->whereIn('status', [
                    TripStopStatus::PENDING,
                    TripStopStatus::EN_ROUTE,
                    TripStopStatus::ARRIVED,
                    TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
                ])
                ->orderBy('sequence_number')
                ->first();

            if ($unhandledStop) {
                $statusLabel = $unhandledStop->status->label();
                $schoolName = $unhandledStop->school?->name ?? "Pemberhentian #{$unhandledStop->sequence_number}";
                throw ValidationException::withMessages([
                    'stops' => [
                        "Armada belum dapat kembali ke SPPG. Pemberhentian #{$unhandledStop->sequence_number} ({$schoolName}) masih berstatus: {$statusLabel}. Semua sekolah harus diselesaikan terlebih dahulu.",
                    ],
                ]);
            }

            // Idempotent: If return has already started, preserve existing timestamp and return
            if ($lockedTrip->return_started_at !== null) {
                return $lockedTrip;
            }

            $now = Carbon::now();
            $lockedTrip->return_started_at = $now;

            if ($notes) {
                $lockedTrip->return_notes = $lockedTrip->return_notes
                    ? $lockedTrip->return_notes . "\n" . trim($notes)
                    : trim($notes);
            }

            $lockedTrip->save();

            ActivityLog::record(
                event: 'trip.return_started',
                auditable: $lockedTrip,
                actor: $actor,
                newValues: [
                    'return_started_at' => $now->toIso8601String(),
                    'return_notes' => $lockedTrip->return_notes,
                ]
            );

            return $lockedTrip;
        });
    }

    /**
     * Mark arrival of the fleet back at the SPPG depot (Gate 3).
     *
     * Recorded as an operational event with server timestamp.
     * Enforces strict state machine: cannot auto-create return_started_at.
     */
    public function recordDepotArrival(Trip $trip, User $actor, ?string $notes = null): Trip
    {
        return DB::transaction(function () use ($trip, $actor, $notes) {
            $lockedTrip = Trip::where('id', $trip->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTrip->status === TripStatus::COMPLETED) {
                throw ValidationException::withMessages([
                    'status' => ['Pencatatan tiba di SPPG tidak dapat dilakukan pada perjalanan yang telah selesai (COMPLETED).'],
                ]);
            }

            if ($lockedTrip->status === TripStatus::CANCELLED) {
                throw ValidationException::withMessages([
                    'status' => ['Pencatatan tiba di SPPG tidak dapat dilakukan pada perjalanan yang telah dibatalkan (CANCELLED).'],
                ]);
            }

            if ($lockedTrip->status !== TripStatus::IN_PROGRESS) {
                throw ValidationException::withMessages([
                    'status' => ['Pencatatan tiba di SPPG hanya dapat dilakukan pada perjalanan yang sedang berlangsung (IN_PROGRESS).'],
                ]);
            }

            // Ensure all stops are handled
            $unhandledStop = $lockedTrip->stops()
                ->whereIn('status', [
                    TripStopStatus::PENDING,
                    TripStopStatus::EN_ROUTE,
                    TripStopStatus::ARRIVED,
                    TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
                ])
                ->orderBy('sequence_number')
                ->first();

            if ($unhandledStop) {
                $statusLabel = $unhandledStop->status->label();
                $schoolName = $unhandledStop->school?->name ?? "Pemberhentian #{$unhandledStop->sequence_number}";
                throw ValidationException::withMessages([
                    'stops' => [
                        "Masih terdapat pemberhentian sekolah yang belum diselesaikan: #{$unhandledStop->sequence_number} ({$schoolName}) masih berstatus {$statusLabel}.",
                    ],
                ]);
            }

            // Gate B: Must have legitimately started return journey; DO NOT auto-create return_started_at!
            if ($lockedTrip->return_started_at === null) {
                throw ValidationException::withMessages([
                    'return_started_at' => ['Armada belum mencatat keberangkatan pulang menuju SPPG. Catat keberangkatan kepulangan terlebih dahulu sebelum mencatat tiba di SPPG.'],
                ]);
            }

            // Idempotent: If arrival already recorded, preserve and return
            if ($lockedTrip->arrived_at_sppg_at !== null) {
                return $lockedTrip;
            }

            $now = Carbon::now();

            if ($now->lt($lockedTrip->return_started_at)) {
                throw ValidationException::withMessages([
                    'arrived_at_sppg_at' => ['Waktu kedatangan di SPPG tidak boleh lebih awal dari waktu mulai kepulangan.'],
                ]);
            }

            $lockedTrip->arrived_at_sppg_at = $now;

            if ($notes) {
                $lockedTrip->return_notes = $lockedTrip->return_notes
                    ? $lockedTrip->return_notes . "\n" . trim($notes)
                    : trim($notes);
            }

            $lockedTrip->save();

            ActivityLog::record(
                event: 'trip.arrived_at_sppg',
                auditable: $lockedTrip,
                actor: $actor,
                newValues: [
                    'arrived_at_sppg_at' => $now->toIso8601String(),
                    'return_notes' => $lockedTrip->return_notes,
                ]
            );

            return $lockedTrip;
        });
    }

    /**
     * Reconcile returned trays / containers upon fleet arrival at SPPG (Gate 4 & Gate C, D, E).
     *
     * Distinguishes trays returned, damaged, and missing without confusing portion counts with trays.
     * Enforces operational stage (arrived_at_sppg_at), immutability on COMPLETED, and incident deduplication.
     */
    public function reconcileTrays(Trip $trip, User $actor, array $data): Trip
    {
        return DB::transaction(function () use ($trip, $actor, $data) {
            $lockedTrip = Trip::where('id', $trip->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Gate D: Guard against mutations on COMPLETED trips
            if ($lockedTrip->status === TripStatus::COMPLETED) {
                throw ValidationException::withMessages([
                    'status' => ['Perjalanan telah berstatus COMPLETED. Rekonsiliasi ompreng tidak dapat diubah setelah perjalanan ditutup secara resmi.'],
                ]);
            }

            if ($lockedTrip->status !== TripStatus::IN_PROGRESS) {
                throw ValidationException::withMessages([
                    'status' => ['Rekonsiliasi ompreng hanya dapat dilakukan pada perjalanan yang sedang berlangsung (IN_PROGRESS).'],
                ]);
            }

            // Gate C: Operational prerequisite: Fleet MUST have arrived at SPPG depot
            if ($lockedTrip->arrived_at_sppg_at === null) {
                throw ValidationException::withMessages([
                    'arrived_at_sppg_at' => ['Rekonsiliasi fisik ompreng hanya dapat dilakukan setelah armada tiba di SPPG (arrived_at_sppg_at).'],
                ]);
            }

            $returned = isset($data['total_trays_returned'])
                ? (int) $data['total_trays_returned']
                : (isset($data['trays_returned']) ? (int) $data['trays_returned'] : null);

            $damaged = isset($data['total_trays_damaged'])
                ? (int) $data['total_trays_damaged']
                : (isset($data['trays_damaged']) ? (int) $data['trays_damaged'] : 0);

            $missing = isset($data['total_trays_missing'])
                ? (int) $data['total_trays_missing']
                : (isset($data['trays_missing']) ? (int) $data['trays_missing'] : 0);

            $notes = isset($data['tray_reconciliation_notes'])
                ? trim((string) $data['tray_reconciliation_notes'])
                : (isset($data['notes']) ? trim((string) $data['notes']) : null);

            // Gate C: returned count must be a non-negative integer (cannot be omitted or null when submitting reconciliation)
            if ($returned === null) {
                throw ValidationException::withMessages([
                    'total_trays_returned' => ['Jumlah wadah kembali wajib diisi dengan angka valid (minimal 0).'],
                ]);
            }

            if ($returned < 0) {
                throw ValidationException::withMessages([
                    'trays_returned' => ['Jumlah wadah kembali tidak boleh bernilai negatif.'],
                    'total_trays_returned' => ['Jumlah wadah kembali tidak boleh bernilai negatif.'],
                ]);
            }

            if ($damaged < 0) {
                throw ValidationException::withMessages([
                    'trays_damaged' => ['Jumlah wadah rusak tidak boleh bernilai negatif.'],
                    'total_trays_damaged' => ['Jumlah wadah rusak tidak boleh bernilai negatif.'],
                ]);
            }

            if ($missing < 0) {
                throw ValidationException::withMessages([
                    'trays_missing' => ['Jumlah wadah hilang tidak boleh bernilai negatif.'],
                    'total_trays_missing' => ['Jumlah wadah hilang tidak boleh bernilai negatif.'],
                ]);
            }

            // Check if baseline is available
            $stopIds = $lockedTrip->stops()->pluck('id');
            $trayReturns = \App\Models\TrayReturn::whereIn('pickup_trip_stop_id', $stopIds)->get();
            $baselineAvailable = $trayReturns->isNotEmpty();
            $baselineExpected = $baselineAvailable ? (int) $trayReturns->sum('expected_trays') : null;

            // Gate D: Idempotency check - if identical values, avoid duplicate log entries
            $isSame = $lockedTrip->total_trays_returned === $returned
                && $lockedTrip->total_trays_damaged === $damaged
                && $lockedTrip->total_trays_missing === $missing
                && (string) $lockedTrip->tray_reconciliation_notes === (string) $notes;

            if ($isSame) {
                return $lockedTrip;
            }

            $lockedTrip->total_trays_returned = $returned;
            $lockedTrip->total_trays_damaged = $damaged;
            $lockedTrip->total_trays_missing = $missing;
            $lockedTrip->tray_reconciliation_notes = $notes;
            $lockedTrip->save();

            // Gate E: Incident Deduplication for TRAY_DISCREPANCY
            $existingIncident = Incident::where('subject_type', Trip::class)
                ->where('subject_id', $lockedTrip->id)
                ->where('category', IncidentCategory::TRAY_DISCREPANCY)
                ->where('status', IncidentStatus::OPEN)
                ->lockForUpdate()
                ->first();

            $hasDiscrepancy = ($damaged > 0 || $missing > 0);

            if ($hasDiscrepancy) {
                $severity = ($damaged > 5 || $missing > 5) ? IncidentSeverity::HIGH : IncidentSeverity::MEDIUM;
                $description = "Selisih fisik ompreng pada Trip {$lockedTrip->trip_code}: {$damaged} rusak, {$missing} hilang." . ($notes ? " Catatan: {$notes}" : '');

                if ($existingIncident) {
                    $oldValues = [
                        'description' => $existingIncident->description,
                        'severity' => $existingIncident->severity->value,
                    ];
                    $existingIncident->description = $description;
                    $existingIncident->severity = $severity;
                    $existingIncident->save();

                    ActivityLog::record(
                        event: 'incident.updated',
                        auditable: $existingIncident,
                        actor: $actor,
                        oldValues: $oldValues,
                        newValues: [
                            'description' => $description,
                            'severity' => $severity->value,
                        ]
                    );
                } else {
                    $incident = Incident::create([
                        'sppg_id' => $lockedTrip->sppg_id,
                        'subject_type' => Trip::class,
                        'subject_id' => $lockedTrip->id,
                        'reported_by' => $actor->id,
                        'category' => IncidentCategory::TRAY_DISCREPANCY,
                        'severity' => $severity,
                        'status' => IncidentStatus::OPEN,
                        'description' => $description,
                    ]);

                    ActivityLog::record(
                        event: 'incident.created',
                        auditable: $incident,
                        actor: $actor,
                        newValues: [
                            'category' => IncidentCategory::TRAY_DISCREPANCY->value,
                            'severity' => $incident->severity->value,
                            'status' => IncidentStatus::OPEN->value,
                            'description' => $description,
                        ]
                    );
                }
            } else {
                // If damaged === 0 and missing === 0, but an OPEN incident exists:
                // Rule: "Jangan otomatis menutup insiden ketika angka diedit menjadi 0. Penyelesaian insiden harus mengikuti kewenangan dan bukti resmi."
                if ($existingIncident) {
                    $correctionNote = " [Koreksi hitungan fisik oleh {$actor->name}: kembali {$returned}, rusak 0, hilang 0. Tiket tetap OPEN menunggu peninjauan dan resolusi resmi Kepala SPPG.]";
                    if (! str_contains($existingIncident->description, 'Koreksi hitungan fisik')) {
                        $oldDescription = $existingIncident->description;
                        $existingIncident->description .= $correctionNote;
                        $existingIncident->save();

                        ActivityLog::record(
                            event: 'incident.updated',
                            auditable: $existingIncident,
                            actor: $actor,
                            oldValues: ['description' => $oldDescription],
                            newValues: ['description' => $existingIncident->description]
                        );
                    }
                }
            }

            ActivityLog::record(
                event: 'trip.trays_reconciled',
                auditable: $lockedTrip,
                actor: $actor,
                newValues: [
                    'total_trays_returned' => $returned,
                    'total_trays_damaged' => $damaged,
                    'total_trays_missing' => $missing,
                    'tray_reconciliation_notes' => $notes,
                    'baseline_expected' => $baselineExpected,
                ]
            );

            return $lockedTrip;
        });
    }

    /**
     * Final Trip Closure & Administrative Sign-off (Gate A, C, D, E, F).
     *
     * Transitions trip to COMPLETED with row locking, idempotency, server timestamps,
     * graceful GPS tracking session shutdown, and preservation of PBI-10 provenance.
     * Enforces prerequisite return, arrival, and tray reconciliation checks.
     */
    public function closeTrip(Trip $trip, User $actor, ?string $notes = null): Trip
    {
        return DB::transaction(function () use ($trip, $actor, $notes) {
            $lockedTrip = Trip::where('id', $trip->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Idempotency: If already completed, return existing completion state without duplicate event (Gate 5 & Gate D)
            if ($lockedTrip->status === TripStatus::COMPLETED) {
                return $lockedTrip;
            }

            if ($lockedTrip->status !== TripStatus::IN_PROGRESS) {
                throw ValidationException::withMessages([
                    'status' => ['Penutupan perjalanan hanya dapat dilakukan pada perjalanan yang sedang berlangsung (IN_PROGRESS).'],
                ]);
            }

            // 1. Validate that all stops on the trip have reached a terminal/handled state (Gate 2)
            $unhandledStop = $lockedTrip->stops()
                ->whereIn('status', [
                    TripStopStatus::PENDING,
                    TripStopStatus::EN_ROUTE,
                    TripStopStatus::ARRIVED,
                    TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
                ])
                ->orderBy('sequence_number')
                ->first();

            if ($unhandledStop) {
                $statusLabel = $unhandledStop->status->label();
                $schoolName = $unhandledStop->school?->name ?? "Pemberhentian #{$unhandledStop->sequence_number}";
                throw ValidationException::withMessages([
                    'stops' => [
                        "Perjalanan belum dapat ditutup. Pemberhentian #{$unhandledStop->sequence_number} ({$schoolName}) masih berstatus: {$statusLabel}. Seluruh pemberhentian wajib diselesaikan terlebih dahulu.",
                    ],
                ]);
            }

            // 2. Gate A: Validate legitimate return and arrival events. NO FABRICATED TIMESTAMPS!
            if ($lockedTrip->return_started_at === null) {
                throw ValidationException::withMessages([
                    'return_started_at' => ['Perjalanan tidak dapat ditutup. Armada belum mencatat waktu mulai kepulangan menuju SPPG.'],
                ]);
            }

            if ($lockedTrip->arrived_at_sppg_at === null) {
                throw ValidationException::withMessages([
                    'arrived_at_sppg_at' => ['Perjalanan tidak dapat ditutup. Armada belum mencatat waktu kedatangan fisik di pangkalan SPPG.'],
                ]);
            }

            if ($lockedTrip->arrived_at_sppg_at->lt($lockedTrip->return_started_at)) {
                throw ValidationException::withMessages([
                    'arrived_at_sppg_at' => ['Waktu kedatangan di SPPG tidak boleh lebih awal dari waktu mulai kepulangan armada.'],
                ]);
            }

            // 3. Gate C: Validate physical tray reconciliation. NULL (unknown) is NOT acceptable. 0 is valid.
            if ($lockedTrip->total_trays_returned === null) {
                throw ValidationException::withMessages([
                    'total_trays_returned' => ['Perjalanan tidak dapat ditutup. Rekonsiliasi fisik ompreng belum dicatat (status masih UNKNOWN). Lakukan rekonsiliasi ompreng terlebih dahulu.'],
                ]);
            }

            // 4. Gate F: Authority & Separation of Duties check
            $stopIds = $lockedTrip->stops()->pluck('id');
            $serviceRecordIds = SchoolServiceRecord::whereIn('trip_stop_id', $stopIds)->pluck('id');
            $trayReturnIds = \App\Models\TrayReturn::whereIn('school_service_record_id', $serviceRecordIds)->pluck('id');

            $hasUnresolvedIncidents = Incident::where(function ($q) use ($lockedTrip, $stopIds, $serviceRecordIds, $trayReturnIds) {
                $q->where(function ($q2) use ($lockedTrip) {
                    $q2->where('subject_type', Trip::class)
                       ->where('subject_id', $lockedTrip->id);
                })->orWhere(function ($q2) use ($stopIds) {
                    $q2->where('subject_type', TripStop::class)
                       ->whereIn('subject_id', $stopIds);
                })->orWhere(function ($q2) use ($serviceRecordIds) {
                    $q2->where('subject_type', SchoolServiceRecord::class)
                       ->whereIn('subject_id', $serviceRecordIds);
                })->orWhere(function ($q2) use ($trayReturnIds) {
                    $q2->where('subject_type', \App\Models\TrayReturn::class)
                       ->whereIn('subject_id', $trayReturnIds);
                });
            })->whereNotIn('status', [IncidentStatus::RESOLVED, IncidentStatus::CLOSED])->exists();

            if ($actor->role === \App\Enums\UserRole::KURIR && $hasUnresolvedIncidents) {
                throw ValidationException::withMessages([
                    'incidents' => ['Perjalanan memiliki insiden aktif yang belum terselesaikan (OPEN, ACKNOWLEDGED, IN_PROGRESS, atau REOPENED). Penutupan perjalanan memerlukan pengesahan administratif resmi oleh Kepala SPPG.'],
                ]);
            }

            if ($actor->role === \App\Enums\UserRole::KEPALA_SPPG && $hasUnresolvedIncidents && (int) $lockedTrip->driver_id === (int) $actor->id) {
                throw ValidationException::withMessages([
                    'separation_of_duties' => ['Kepala SPPG yang bertindak sebagai pengemudi tidak dapat melakukan self-approval penutupan perjalanan yang memiliki insiden belum terselesaikan.'],
                ]);
            }

            $now = Carbon::now();

            // 5. Gate 6: Preserve PBI-10 Unallocated Data and Document Exceptions
            $stops = $lockedTrip->stops()->with(['deliveryItems', 'schoolServiceRecord', 'qualityChecks'])->get();
            $unallocatedBatchCount = 0;
            $documentedExceptions = [];

            foreach ($stops as $stop) {
                foreach ($stop->deliveryItems as $item) {
                    if ($item->delivered_portions === null && $stop->schoolServiceRecord && $stop->schoolServiceRecord->status === SchoolReceiptStatus::RECEIVED) {
                        $unallocatedBatchCount++;
                    }
                }

                if ($stop->status === TripStopStatus::EXCEPTION) {
                    $documentedExceptions[] = "Stop #{$stop->sequence_number} ({$stop->school?->name}): Status Pengecualian (EXCEPTION).";
                }

                if ($stop->schoolServiceRecord) {
                    if ($stop->schoolServiceRecord->isHeld()) {
                        $documentedExceptions[] = "Stop #{$stop->sequence_number} ({$stop->school?->name}): Ditahan (HELD) oleh PIC Sekolah.";
                    } elseif ($stop->schoolServiceRecord->hasDiscrepancy()) {
                        $shortage = $stop->schoolServiceRecord->shortage_portions ?? 0;
                        $damaged = $stop->schoolServiceRecord->damaged_portions ?? 0;
                        $documentedExceptions[] = "Stop #{$stop->sequence_number} ({$stop->school?->name}): Selisih penerimaan ({$shortage} kurang, {$damaged} rusak).";
                    }
                }
            }

            // 6. Terminate active GPS tracking session gracefully (Gate 5)
            $lockedTrip->active_tracking_session_id = null;
            $lockedTrip->tracking_status = 'OFFLINE';
            $lockedTrip->tracking_ended_at = $now;

            // 7. Finalize Trip Status
            $lockedTrip->status = TripStatus::COMPLETED;
            $lockedTrip->completed_at = $now;
            $lockedTrip->closed_by = $actor->id;

            if ($notes) {
                $lockedTrip->return_notes = $lockedTrip->return_notes
                    ? $lockedTrip->return_notes . "\n" . trim($notes)
                    : trim($notes);
            }

            $lockedTrip->save();

            // 8. Record official immutable Audit Log event (Gate 5 & Gate 6)
            ActivityLog::record(
                event: 'trip.completed',
                auditable: $lockedTrip,
                actor: $actor,
                newValues: [
                    'status' => TripStatus::COMPLETED->value,
                    'completed_at' => $now->toIso8601String(),
                    'closed_by' => $actor->id,
                    'closed_by_name' => $actor->name,
                    'closed_by_role' => $actor->role?->value,
                    'return_started_at' => $lockedTrip->return_started_at?->toIso8601String(),
                    'arrived_at_sppg_at' => $lockedTrip->arrived_at_sppg_at?->toIso8601String(),
                    'unallocated_mode_b_batches_count' => $unallocatedBatchCount,
                    'documented_exceptions' => $documentedExceptions,
                    'total_trays_returned' => $lockedTrip->total_trays_returned,
                    'total_trays_damaged' => $lockedTrip->total_trays_damaged,
                    'total_trays_missing' => $lockedTrip->total_trays_missing,
                ]
            );

            return $lockedTrip;
        });
    }

    /**
     * Calculate readiness evaluation for trip closure and return (Gate 2 & Gate 8, Gate C).
     */
    public function getClosureReadiness(Trip $trip): array
    {
        $trip->loadMissing([
            'sppg',
            'driver',
            'vehicle',
            'closedBy',
            'stops.school',
            'stops.deliveryItems.foodBatch',
            'stops.schoolServiceRecord.qualityChecks',
        ]);

        $stops = $trip->stops->sortBy('sequence_number')->values();
        $isCompleted = $trip->status === TripStatus::COMPLETED;
        $returnStarted = $trip->return_started_at !== null;
        $arrivedDepot = $trip->arrived_at_sppg_at !== null;
        $traysReconciled = $trip->total_trays_returned !== null;

        $blockers = [];
        $exceptions = [];
        $stopsStatus = [];
        $unhandledCount = 0;
        $modeBUnallocatedCount = 0;

        foreach ($stops as $stop) {
            $isHandled = in_array($stop->status, [
                TripStopStatus::HANDED_OVER,
                TripStopStatus::COMPLETED,
                TripStopStatus::SKIPPED,
                TripStopStatus::EXCEPTION,
            ], true);

            if (! $isHandled) {
                $unhandledCount++;
                $blockers[] = "Pemberhentian #{$stop->sequence_number} ({$stop->school?->name}) masih berstatus {$stop->status->label()}.";
            }

            // Check Mode B unallocated batches (Gate 6)
            $hasModeB = false;
            foreach ($stop->deliveryItems as $item) {
                if ($item->delivered_portions === null && $stop->schoolServiceRecord?->status === SchoolReceiptStatus::RECEIVED) {
                    $hasModeB = true;
                    $modeBUnallocatedCount++;
                }
            }

            if ($stop->schoolServiceRecord) {
                if ($stop->schoolServiceRecord->isHeld()) {
                    $exceptions[] = [
                        'type' => 'HELD',
                        'school' => $stop->school?->name,
                        'stop_sequence' => $stop->sequence_number,
                        'message' => 'Layanan makanan ditahan oleh pihak sekolah (HELD).',
                        'responsible_actor' => 'Pengawas Gizi & Kepala SPPG',
                    ];
                } elseif ($stop->schoolServiceRecord->hasDiscrepancy()) {
                    $exceptions[] = [
                        'type' => 'DISCREPANCY',
                        'school' => $stop->school?->name,
                        'stop_sequence' => $stop->sequence_number,
                        'message' => "Selisih fisik: {$stop->schoolServiceRecord->shortage_portions} kurang, {$stop->schoolServiceRecord->damaged_portions} rusak.",
                        'responsible_actor' => 'Kepala SPPG & Asisten Lapangan',
                    ];
                }
            }

            $stopsStatus[] = [
                'stop_id' => $stop->id,
                'sequence_number' => $stop->sequence_number,
                'school_name' => $stop->school?->name ?? "Stop #{$stop->sequence_number}",
                'status' => $stop->status->value,
                'status_label' => $stop->status->label(),
                'badge_class' => $stop->status->badgeClass(),
                'is_handled' => $isHandled,
                'has_mode_b_unallocated' => $hasModeB,
                'service_record_status' => $stop->schoolServiceRecord?->status?->value,
            ];
        }

        $allStopsHandled = $unhandledCount === 0;

        // Return and Arrival blockers
        if (! $returnStarted) {
            $blockers[] = 'Armada belum mencatat keberangkatan pulang menuju SPPG.';
        }

        if (! $arrivedDepot) {
            $blockers[] = 'Armada belum mencatat kedatangan fisik di pangkalan SPPG.';
        }

        if (! $traysReconciled) {
            $blockers[] = 'Rekonsiliasi fisik ompreng belum dicatat (wajib sebelum penutupan).';
        }

        // Check unresolved incidents
        $stopIds = $stops->pluck('id');
        $serviceRecordIds = SchoolServiceRecord::whereIn('trip_stop_id', $stopIds)->pluck('id');
        $trayReturnIds = \App\Models\TrayReturn::whereIn('school_service_record_id', $serviceRecordIds)->pluck('id');

        $hasUnresolvedIncidents = Incident::where(function ($q) use ($trip, $stopIds, $serviceRecordIds, $trayReturnIds) {
            $q->where(function ($q2) use ($trip) {
                $q2->where('subject_type', Trip::class)
                   ->where('subject_id', $trip->id);
            })->orWhere(function ($q2) use ($stopIds) {
                $q2->where('subject_type', TripStop::class)
                   ->whereIn('subject_id', $stopIds);
            })->orWhere(function ($q2) use ($serviceRecordIds) {
                $q2->where('subject_type', SchoolServiceRecord::class)
                   ->whereIn('subject_id', $serviceRecordIds);
            })->orWhere(function ($q2) use ($trayReturnIds) {
                $q2->where('subject_type', \App\Models\TrayReturn::class)
                   ->whereIn('subject_id', $trayReturnIds);
            });
        })->whereNotIn('status', [IncidentStatus::RESOLVED, IncidentStatus::CLOSED])->exists();

        $hasOpenIncidents = $hasUnresolvedIncidents;

        if ($hasUnresolvedIncidents) {
            $exceptions[] = [
                'type' => 'OPEN_INCIDENT',
                'school' => null,
                'stop_sequence' => null,
                'message' => 'Terdapat insiden aktif yang belum terselesaikan pada perjalanan ini. Penutupan administratif wajib disahkan oleh Kepala SPPG.',
                'responsible_actor' => 'Kepala SPPG',
            ];
        }

        $canReturn = $allStopsHandled && ! $isCompleted && $trip->status === TripStatus::IN_PROGRESS;
        $canArriveDepot = $allStopsHandled && $returnStarted && ! $isCompleted && $trip->status === TripStatus::IN_PROGRESS;
        $canReconcileTrays = $allStopsHandled && $returnStarted && $arrivedDepot && ! $isCompleted && $trip->status === TripStatus::IN_PROGRESS;
        $canClose = $allStopsHandled && $returnStarted && $arrivedDepot && $traysReconciled && ! $isCompleted && $trip->status === TripStatus::IN_PROGRESS;

        return [
            'is_completed' => $isCompleted,
            'completed_at' => $trip->completed_at?->toIso8601String(),
            'closed_by' => $trip->closedBy?->name,
            'return_started' => $returnStarted,
            'return_started_at' => $trip->return_started_at?->toIso8601String(),
            'arrived_depot' => $arrivedDepot,
            'arrived_at_sppg_at' => $trip->arrived_at_sppg_at?->toIso8601String(),
            'trays_reconciled' => $traysReconciled,
            'has_open_incidents' => $hasOpenIncidents,
            'all_stops_handled' => $allStopsHandled,
            'can_return' => $canReturn,
            'can_arrive_depot' => $canArriveDepot,
            'can_reconcile_trays' => $canReconcileTrays,
            'can_close' => $canClose,
            'blockers' => $blockers,
            'exceptions' => $exceptions,
            'mode_b_unallocated_count' => $modeBUnallocatedCount,
            'mode_b_unallocated_stops' => $modeBUnallocatedCount,
            'stops_status' => $stopsStatus,
            'tray_summary' => [
                'total_returned' => $trip->total_trays_returned,
                'total_damaged' => $trip->total_trays_damaged,
                'total_missing' => $trip->total_trays_missing,
                'notes' => $trip->tray_reconciliation_notes,
            ],
        ];
    }
}
