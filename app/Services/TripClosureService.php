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
     */
    public function recordDepotArrival(Trip $trip, User $actor, ?string $notes = null): Trip
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
                    'status' => ['Pencatatan tiba di SPPG hanya dapat dilakukan pada perjalanan yang sedang berlangsung.'],
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
                ->first();

            if ($unhandledStop) {
                throw ValidationException::withMessages([
                    'stops' => ['Masih terdapat pemberhentian sekolah yang belum diselesaikan.'],
                ]);
            }

            // Idempotent: If arrival already recorded, preserve and return
            if ($lockedTrip->arrived_at_sppg_at !== null) {
                return $lockedTrip;
            }

            $now = Carbon::now();

            // If return was not explicitly started earlier, set return_started_at now
            if ($lockedTrip->return_started_at === null) {
                $lockedTrip->return_started_at = $now;
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
     * Reconcile returned trays / containers upon fleet arrival at SPPG (Gate 4).
     *
     * Distinguishes trays returned, damaged, and missing without confusing portion counts with trays.
     */
    public function reconcileTrays(Trip $trip, User $actor, array $data): Trip
    {
        return DB::transaction(function () use ($trip, $actor, $data) {
            $lockedTrip = Trip::where('id', $trip->id)
                ->lockForUpdate()
                ->firstOrFail();

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

            if ($returned !== null && $returned < 0) {
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

            $lockedTrip->total_trays_returned = $returned;
            $lockedTrip->total_trays_damaged = $damaged;
            $lockedTrip->total_trays_missing = $missing;
            $lockedTrip->tray_reconciliation_notes = $notes;
            $lockedTrip->save();

            // Record incident if trays are damaged or missing (Gate 4)
            if ($damaged > 0 || $missing > 0) {
                Incident::create([
                    'sppg_id' => $lockedTrip->sppg_id,
                    'subject_type' => Trip::class,
                    'subject_id' => $lockedTrip->id,
                    'reported_by' => $actor->id,
                    'category' => IncidentCategory::TRAY_DISCREPANCY,
                    'severity' => ($damaged > 5 || $missing > 5) ? IncidentSeverity::HIGH : IncidentSeverity::MEDIUM,
                    'status' => IncidentStatus::OPEN,
                    'description' => "Selisih fisik ompreng pada Trip {$lockedTrip->trip_code}: {$damaged} rusak, {$missing} hilang. " . ($notes ? "Catatan: {$notes}" : ''),
                ]);
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
                ]
            );

            return $lockedTrip;
        });
    }

    /**
     * Final Trip Closure & Administrative Sign-off (Gate 5 & Gate 6).
     *
     * Transitions trip to COMPLETED with row locking, idempotency, server timestamps,
     * graceful GPS tracking session shutdown, and preservation of PBI-10 provenance.
     */
    public function closeTrip(Trip $trip, User $actor, ?string $notes = null): Trip
    {
        return DB::transaction(function () use ($trip, $actor, $notes) {
            $lockedTrip = Trip::where('id', $trip->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Idempotency: If already completed, return existing completion state without duplicate event (Gate 5)
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

            // 2. Validate fleet return to SPPG depot (Gate 3 & Gate 5)
            $now = Carbon::now();

            if ($lockedTrip->return_started_at === null) {
                $lockedTrip->return_started_at = $now;
            }

            if ($lockedTrip->arrived_at_sppg_at === null) {
                $lockedTrip->arrived_at_sppg_at = $now;
            }

            // 3. Gate 6: Preserve PBI-10 Unallocated Data and Document Exceptions
            // Mode B stops may have delivered_portions = null or QualityCheck.accepted_portions = null.
            // These null values represent unallocated attribution and MUST NOT be converted to 0.
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

            // 4. Terminate active GPS tracking session gracefully (Gate 5)
            // Ensures old sessions cannot send active telemetry after closure, while preserving all historical records
            $lockedTrip->active_tracking_session_id = null;
            $lockedTrip->tracking_status = 'OFFLINE';
            $lockedTrip->tracking_ended_at = $now;

            // 5. Finalize Trip Status
            $lockedTrip->status = TripStatus::COMPLETED;
            $lockedTrip->completed_at = $now;
            $lockedTrip->closed_by = $actor->id;

            if ($notes) {
                $lockedTrip->return_notes = $lockedTrip->return_notes
                    ? $lockedTrip->return_notes . "\n" . trim($notes)
                    : trim($notes);
            }

            $lockedTrip->save();

            // 6. Record official immutable Audit Log event (Gate 5 & Gate 6)
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
     * Calculate readiness evaluation for trip closure and return (Gate 2 & Gate 8).
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
        $canReturn = $allStopsHandled && ! $isCompleted && $trip->status === TripStatus::IN_PROGRESS;
        $canArriveDepot = $allStopsHandled && ! $isCompleted && $trip->status === TripStatus::IN_PROGRESS;
        $canClose = $allStopsHandled && ! $isCompleted && $trip->status === TripStatus::IN_PROGRESS;

        return [
            'is_completed' => $isCompleted,
            'completed_at' => $trip->completed_at?->toIso8601String(),
            'closed_by' => $trip->closedBy?->name,
            'return_started' => $returnStarted,
            'return_started_at' => $trip->return_started_at?->toIso8601String(),
            'arrived_depot' => $arrivedDepot,
            'arrived_at_sppg_at' => $trip->arrived_at_sppg_at?->toIso8601String(),
            'all_stops_handled' => $allStopsHandled,
            'can_return' => $canReturn,
            'can_arrive_depot' => $canArriveDepot,
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
