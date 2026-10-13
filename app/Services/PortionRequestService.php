<?php

namespace App\Services;

use App\Enums\PortionRequestStatus;
use App\Models\ActivityLog;
use App\Models\DailyPortionRequest;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PortionRequestService
{
    /**
     * Read-only fetch for a portion request on a given date without mutating database.
     * Prevents unintended business record creation during GET page requests.
     */
    public function getForDate(School $school, string $serviceDate): ?DailyPortionRequest
    {
        return DailyPortionRequest::where('school_id', $school->id)
            ->whereDate('service_date', $serviceDate)
            ->first();
    }

    /**
     * Get existing request or initialize a new draft for the given school and service date.
     * Only called when explicit write action is executed.
     */
    public function getOrCreateForDate(School $school, string $serviceDate, User $actor): DailyPortionRequest
    {
        return DB::transaction(function () use ($school, $serviceDate, $actor) {
            $request = DailyPortionRequest::where('school_id', $school->id)
                ->whereDate('service_date', $serviceDate)
                ->lockForUpdate()
                ->first();

            if (! $request) {
                $request = DailyPortionRequest::create([
                    'school_id' => $school->id,
                    'service_date' => $serviceDate,
                    'registered_count' => (int) $school->registered_beneficiaries,
                    'present_count' => null,
                    'requested_portions' => null,
                    'status' => PortionRequestStatus::DRAFT,
                ]);

                ActivityLog::record(
                    event: 'portion_request.draft_initialized',
                    auditable: $request,
                    actor: $actor,
                    newValues: [
                        'school_id' => $school->id,
                        'service_date' => $serviceDate,
                        'registered_count' => $request->registered_count,
                        'status' => PortionRequestStatus::DRAFT->value,
                    ]
                );
            }

            return $request;
        });
    }

    /**
     * Save draft portion values before formal confirmation.
     * Concurrency-safe against duplicate submissions.
     */
    public function saveDraft(School $school, string $serviceDate, array $data, User $actor): DailyPortionRequest
    {
        return DB::transaction(function () use ($school, $serviceDate, $data, $actor) {
            $portionRequest = DailyPortionRequest::where('school_id', $school->id)
                ->whereDate('service_date', $serviceDate)
                ->lockForUpdate()
                ->first();

            $isNew = false;
            if (! $portionRequest) {
                $portionRequest = DailyPortionRequest::create([
                    'school_id' => $school->id,
                    'service_date' => $serviceDate,
                    'registered_count' => (int) $school->registered_beneficiaries,
                    'present_count' => null,
                    'requested_portions' => null,
                    'status' => PortionRequestStatus::DRAFT,
                ]);
                $isNew = true;
            }

            if (! $portionRequest->isDraft()) {
                throw ValidationException::withMessages([
                    'status' => ['Permintaan untuk tanggal ini sudah dikonfirmasi atau terkunci dan tidak dapat diubah sebagai draft.'],
                ]);
            }

            if ($portionRequest->isPastCutoff()) {
                throw ValidationException::withMessages([
                    'cutoff' => ['Batas waktu (cutoff) penyimpanan draft untuk tanggal layanan ini telah terlewati.'],
                ]);
            }

            $presentCount = isset($data['present_count']) && $data['present_count'] !== '' && $data['present_count'] !== null
                ? (int) $data['present_count']
                : null;

            $requestedPortions = isset($data['requested_portions']) && $data['requested_portions'] !== '' && $data['requested_portions'] !== null
                ? (int) $data['requested_portions']
                : null;

            if ($presentCount !== null && $presentCount < 0) {
                throw ValidationException::withMessages([
                    'present_count' => ['Jumlah siswa hadir tidak boleh bernilai negatif.'],
                ]);
            }

            if ($requestedPortions !== null && $requestedPortions < 0) {
                throw ValidationException::withMessages([
                    'requested_portions' => ['Jumlah porsi yang diminta tidak boleh bernilai negatif.'],
                ]);
            }

            $oldValues = [
                'present_count' => $portionRequest->present_count,
                'requested_portions' => $portionRequest->requested_portions,
            ];

            $portionRequest->update([
                'present_count' => $presentCount,
                'requested_portions' => $requestedPortions,
            ]);

            ActivityLog::record(
                event: $isNew ? 'portion_request.draft_initialized' : 'portion_request.draft_saved',
                auditable: $portionRequest,
                actor: $actor,
                oldValues: $isNew ? null : $oldValues,
                newValues: [
                    'present_count' => $presentCount,
                    'requested_portions' => $requestedPortions,
                ]
            );

            return $portionRequest;
        });
    }

    /**
     * Formally confirm daily portion request for a school.
     */
    public function confirmRequest(DailyPortionRequest $portionRequest, array $data, User $pic): DailyPortionRequest
    {
        return DB::transaction(function () use ($portionRequest, $data, $pic) {
            $locked = DailyPortionRequest::where('id', $portionRequest->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isDraft() && ! $locked->isConfirmed()) {
                throw ValidationException::withMessages([
                    'status' => ['Hanya permintaan berstatus Draft atau Terkonfirmasi yang dapat diproses konfirmasi.'],
                ]);
            }

            // Cutoff validation
            if ($locked->isPastCutoff()) {
                throw ValidationException::withMessages([
                    'cutoff' => ['Batas waktu (cutoff) konfirmasi porsi untuk tanggal ' . $locked->service_date->translatedFormat('d F Y') . ' telah terlewati. Hubungi Kepala SPPG untuk prosedur khusus.'],
                ]);
            }

            if (! array_key_exists('requested_portions', $data) || $data['requested_portions'] === null || $data['requested_portions'] === '') {
                throw ValidationException::withMessages([
                    'requested_portions' => ['Kebutuhan jumlah porsi wajib diisi sebelum melakukan konfirmasi.'],
                ]);
            }

            $requested = (int) $data['requested_portions'];
            if ($requested < 0) {
                throw ValidationException::withMessages([
                    'requested_portions' => ['Jumlah porsi yang diminta tidak boleh bernilai negatif.'],
                ]);
            }

            $present = isset($data['present_count']) && $data['present_count'] !== '' && $data['present_count'] !== null
                ? (int) $data['present_count']
                : null;

            if ($present !== null && $present < 0) {
                throw ValidationException::withMessages([
                    'present_count' => ['Jumlah kehadiran tidak boleh bernilai negatif.'],
                ]);
            }

            // Exceeding registered beneficiaries rule: reason is required
            $reason = isset($data['revision_reason']) ? trim((string) $data['revision_reason']) : null;
            if ($requested > $locked->registered_count && empty($reason)) {
                throw ValidationException::withMessages([
                    'revision_reason' => ['Jumlah porsi yang diminta (' . number_format($requested) . ') melebihi jumlah siswa terdaftar (' . number_format($locked->registered_count) . '). Alasan justifikasi wajib disertakan.'],
                ]);
            }

            $oldStatus = $locked->status->value;

            $locked->update([
                'requested_portions' => $requested,
                'present_count' => $present,
                'status' => PortionRequestStatus::CONFIRMED,
                'confirmed_by' => $pic->id,
                'confirmed_at' => now(),
                'revision_reason' => $reason,
                'is_exceeds_approved' => false, // Reset/require approval if exceeding registered
            ]);

            ActivityLog::record(
                event: 'portion_request.confirmed',
                auditable: $locked,
                actor: $pic,
                oldValues: ['status' => $oldStatus],
                newValues: [
                    'requested_portions' => $requested,
                    'present_count' => $present,
                    'status' => PortionRequestStatus::CONFIRMED->value,
                    'confirmed_by' => $pic->id,
                    'confirmed_at' => $locked->confirmed_at->toIso8601String(),
                ]
            );

            return $locked;
        });
    }

    /**
     * Transition a portion request from CONFIRMED -> LOCKED.
     * Authorized process (Kepala SPPG or production plan finalization).
     * Idempotent against double locking.
     */
    public function lockRequest(DailyPortionRequest $portionRequest, User $actor): DailyPortionRequest
    {
        return DB::transaction(function () use ($portionRequest, $actor) {
            $locked = DailyPortionRequest::where('id', $portionRequest->id)->lockForUpdate()->firstOrFail();

            // Idempotency: if already locked, return safely without duplicate log
            if ($locked->isLocked()) {
                return $locked;
            }

            // Allowed transitions to LOCKED: from CONFIRMED
            if (! in_array($locked->status, [PortionRequestStatus::CONFIRMED], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Hanya permintaan dengan status Terkonfirmasi (CONFIRMED) yang dapat dikunci untuk produksi. Status saat ini: ' . $locked->status->label()],
                ]);
            }

            $oldStatus = $locked->status->value;
            $locked->update([
                'status' => PortionRequestStatus::LOCKED,
                'locked_at' => now(),
            ]);

            ActivityLog::record(
                event: 'portion_request.locked',
                auditable: $locked,
                actor: $actor,
                oldValues: ['status' => $oldStatus],
                newValues: [
                    'status' => PortionRequestStatus::LOCKED->value,
                    'locked_at' => $locked->locked_at->toIso8601String(),
                ]
            );

            return $locked;
        });
    }

    /**
     * Submit an authorized revision for a confirmed or locked portion request.
     * CRITICAL INTEGRITY: Does NOT overwrite official requested_portions until approved!
     * Stores proposed count in revised_portions and keeps history intact.
     */
    public function submitRevision(DailyPortionRequest $portionRequest, array $data, User $actor): DailyPortionRequest
    {
        return DB::transaction(function () use ($portionRequest, $data, $actor) {
            $locked = DailyPortionRequest::where('id', $portionRequest->id)->lockForUpdate()->firstOrFail();

            if (! $locked->canBeRevised()) {
                throw ValidationException::withMessages([
                    'status' => ['Revisi hanya dapat diajukan untuk permintaan yang sudah Berstatus Konfirmasi atau Terkunci.'],
                ]);
            }

            $reason = trim($data['revision_reason'] ?? '');
            if (empty($reason)) {
                throw ValidationException::withMessages([
                    'revision_reason' => ['Alasan revisi wajib dicantumkan secara lengkap untuk keperluan jejak audit operasional.'],
                ]);
            }

            if (! array_key_exists('requested_portions', $data) || $data['requested_portions'] === null || $data['requested_portions'] === '') {
                throw ValidationException::withMessages([
                    'requested_portions' => ['Jumlah porsi revisi wajib diisi.'],
                ]);
            }

            $revisedPortions = (int) $data['requested_portions'];
            if ($revisedPortions < 0) {
                throw ValidationException::withMessages([
                    'requested_portions' => ['Jumlah porsi tidak boleh bernilai negatif.'],
                ]);
            }

            $present = isset($data['present_count']) && $data['present_count'] !== '' && $data['present_count'] !== null
                ? (int) $data['present_count']
                : $locked->present_count;

            $oldValues = [
                'requested_portions' => $locked->requested_portions,
                'present_count' => $locked->present_count,
                'status' => $locked->status->value,
                'revision_reason' => $locked->revision_reason,
            ];

            $locked->update([
                'revised_portions' => $revisedPortions,
                'previous_status' => $locked->status->value,
                'present_count' => $present,
                'status' => PortionRequestStatus::REVISED,
                'revision_reason' => $reason,
                'revision_requested_at' => now(),
                'revision_reviewed_by' => null,
                'revision_reviewed_at' => null,
                'revision_rejection_reason' => null,
            ]);

            ActivityLog::record(
                event: 'portion_request.revised',
                auditable: $locked,
                actor: $actor,
                oldValues: $oldValues,
                newValues: [
                    'requested_portions' => $locked->requested_portions,
                    'revised_portions' => $revisedPortions,
                    'present_count' => $present,
                    'status' => PortionRequestStatus::REVISED->value,
                    'revision_reason' => $reason,
                ]
            );

            return $locked;
        });
    }

    /**
     * Approve a pending revision (Kepala SPPG authorization).
     * Formally commits the revised portion amount into requested_portions and transitions to LOCKED.
     */
    public function approveRevision(DailyPortionRequest $portionRequest, User $actor): DailyPortionRequest
    {
        return DB::transaction(function () use ($portionRequest, $actor) {
            $locked = DailyPortionRequest::where('id', $portionRequest->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isRevised() || $locked->revised_portions === null) {
                throw ValidationException::withMessages([
                    'status' => ['Hanya permintaan dengan revisi aktif yang dapat disetujui.'],
                ]);
            }

            $oldValues = [
                'requested_portions' => $locked->requested_portions,
                'status' => $locked->status->value,
            ];

            $locked->update([
                'requested_portions' => $locked->revised_portions,
                'revised_portions' => null,
                'status' => PortionRequestStatus::LOCKED,
                'locked_at' => now(),
                'revision_reviewed_by' => $actor->id,
                'revision_reviewed_at' => now(),
            ]);

            ActivityLog::record(
                event: 'portion_request.revision_approved',
                auditable: $locked,
                actor: $actor,
                oldValues: $oldValues,
                newValues: [
                    'requested_portions' => $locked->requested_portions,
                    'status' => PortionRequestStatus::LOCKED->value,
                    'locked_at' => $locked->locked_at->toIso8601String(),
                    'approved_by' => $actor->id,
                ]
            );

            return $locked;
        });
    }

    /**
     * Reject a pending revision (Kepala SPPG authorization).
     * Retains original official requested_portions and reverts status to previous valid state.
     */
    public function rejectRevision(DailyPortionRequest $portionRequest, string $reason, User $actor): DailyPortionRequest
    {
        return DB::transaction(function () use ($portionRequest, $reason, $actor) {
            $locked = DailyPortionRequest::where('id', $portionRequest->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isRevised()) {
                throw ValidationException::withMessages([
                    'status' => ['Hanya permintaan berstatus Revisi yang dapat ditolak.'],
                ]);
            }

            $trimmedReason = trim($reason);
            if (empty($trimmedReason)) {
                throw ValidationException::withMessages([
                    'reason' => ['Alasan penolakan revisi wajib disertakan.'],
                ]);
            }

            $previousStatus = $locked->previous_status ? PortionRequestStatus::tryFrom($locked->previous_status) : PortionRequestStatus::CONFIRMED;
            $targetStatus = $previousStatus ?? PortionRequestStatus::CONFIRMED;

            $locked->update([
                'revised_portions' => null,
                'status' => $targetStatus,
                'revision_rejection_reason' => $trimmedReason,
                'revision_reviewed_by' => $actor->id,
                'revision_reviewed_at' => now(),
            ]);

            ActivityLog::record(
                event: 'portion_request.revision_rejected',
                auditable: $locked,
                actor: $actor,
                oldValues: ['status' => PortionRequestStatus::REVISED->value],
                newValues: [
                    'status' => $targetStatus->value,
                    'rejection_reason' => $trimmedReason,
                    'rejected_by' => $actor->id,
                ]
            );

            return $locked;
        });
    }

    /**
     * Authorize exception when requested portions exceed registered count (Kepala SPPG).
     */
    public function approveExceedingException(DailyPortionRequest $portionRequest, User $actor): DailyPortionRequest
    {
        return DB::transaction(function () use ($portionRequest, $actor) {
            $locked = DailyPortionRequest::where('id', $portionRequest->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isExceedingRegistered()) {
                throw ValidationException::withMessages([
                    'status' => ['Permintaan ini tidak melebihi kuota siswa terdaftar.'],
                ]);
            }

            $locked->update([
                'is_exceeds_approved' => true,
                'exceeds_approved_by' => $actor->id,
                'exceeds_approved_at' => now(),
            ]);

            ActivityLog::record(
                event: 'portion_request.exceeding_approved',
                auditable: $locked,
                actor: $actor,
                newValues: [
                    'requested_portions' => $locked->requested_portions,
                    'registered_count' => $locked->registered_count,
                    'is_exceeds_approved' => true,
                    'approved_by' => $actor->id,
                ]
            );

            return $locked;
        });
    }

    /**
     * Reject exception when requested portions exceed registered count.
     * Clamps requested_portions to registered_count.
     */
    public function rejectExceedingException(DailyPortionRequest $portionRequest, string $reason, User $actor): DailyPortionRequest
    {
        return DB::transaction(function () use ($portionRequest, $reason, $actor) {
            $locked = DailyPortionRequest::where('id', $portionRequest->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isExceedingRegistered()) {
                throw ValidationException::withMessages([
                    'status' => ['Permintaan ini tidak melebihi kuota siswa terdaftar.'],
                ]);
            }

            $trimmedReason = trim($reason);
            $oldRequested = $locked->requested_portions;

            $locked->update([
                'requested_portions' => $locked->registered_count,
                'is_exceeds_approved' => false,
                'exceeds_rejection_reason' => $trimmedReason,
                'exceeds_approved_by' => $actor->id,
                'exceeds_approved_at' => now(),
            ]);

            ActivityLog::record(
                event: 'portion_request.exceeding_rejected',
                auditable: $locked,
                actor: $actor,
                oldValues: ['requested_portions' => $oldRequested],
                newValues: [
                    'requested_portions' => $locked->registered_count,
                    'rejection_reason' => $trimmedReason,
                    'rejected_by' => $actor->id,
                ]
            );

            return $locked;
        });
    }

    /**
     * Cancel meal service request for a specific school date (e.g. school holiday or emergency).
     */
    public function cancelRequest(DailyPortionRequest $portionRequest, string $reason, User $actor): DailyPortionRequest
    {
        return DB::transaction(function () use ($portionRequest, $reason, $actor) {
            $locked = DailyPortionRequest::where('id', $portionRequest->id)->lockForUpdate()->firstOrFail();

            if ($locked->isCancelled()) {
                throw ValidationException::withMessages([
                    'status' => ['Permintaan untuk tanggal ini sudah berstatus Dibatalkan sebelumnya.'],
                ]);
            }

            if ($locked->isUsedInFinalizedProductionPlan()) {
                throw ValidationException::withMessages([
                    'status' => ['Permintaan ini telah digunakan dalam Rencana Produksi Final. Pembatalan memerlukan koordinasi langsung dengan Kepala SPPG.'],
                ]);
            }

            $trimmedReason = trim($reason);
            if (empty($trimmedReason)) {
                throw ValidationException::withMessages([
                    'reason' => ['Alasan pembatalan layanan sekolah wajib dicantumkan.'],
                ]);
            }

            $oldStatus = $locked->status->value;

            $locked->update([
                'status' => PortionRequestStatus::CANCELLED,
                'revision_reason' => $trimmedReason,
            ]);

            ActivityLog::record(
                event: 'portion_request.cancelled',
                auditable: $locked,
                actor: $actor,
                oldValues: ['status' => $oldStatus],
                newValues: [
                    'status' => PortionRequestStatus::CANCELLED->value,
                    'reason' => $trimmedReason,
                ]
            );

            return $locked;
        });
    }

    /**
     * Calculate operational portion aggregates for Kepala SPPG.
     * CRITICAL RULES:
     * 1. Pending schools are NOT assumed to be 0!
     * 2. REVISED schools are NOT counted in official confirmed portions until approved!
     * 3. Schools with unapproved exceeding exceptions are segregated.
     */
    public function getSppgAggregateForDate(int $sppgId, string $serviceDate): array
    {
        $schools = School::where('sppg_id', $sppgId)
            ->where('is_active', true)
            ->with(['portionRequests' => function ($q) use ($serviceDate) {
                $q->whereDate('service_date', $serviceDate);
            }, 'picUsers'])
            ->get();

        $confirmedSchools = [];
        $revisedSchools = [];
        $pendingSchools = [];
        $cancelledSchools = [];
        $unapprovedExceptions = [];
        $totalConfirmedPortions = 0;

        foreach ($schools as $school) {
            $request = $school->portionRequests->first();

            if (! $request || $request->isDraft()) {
                // PENDING: Has no request yet, or only in DRAFT.
                // Do NOT assume zero!
                $pendingSchools[] = [
                    'school' => $school,
                    'request' => $request,
                    'registered_count' => $school->registered_beneficiaries,
                    'pic_name' => $school->picUsers->first()?->name ?? 'Belum ada PIC',
                    'cutoff_datetime' => $request ? $request->cutoffDateTime() : Carbon::parse($serviceDate, 'Asia/Jakarta')->subDay()->setTime(16, 0),
                    'is_past_cutoff' => $request ? $request->isPastCutoff() : Carbon::now('Asia/Jakarta')->greaterThanOrEqualTo(Carbon::parse($serviceDate, 'Asia/Jakarta')->subDay()->setTime(16, 0)),
                ];
            } elseif ($request->isCancelled()) {
                $cancelledSchools[] = [
                    'school' => $school,
                    'request' => $request,
                    'reason' => $request->revision_reason,
                ];
            } elseif ($request->isRevised()) {
                // REVISED: Pending authorization! NOT counted in confirmed portions!
                $revisedSchools[] = [
                    'school' => $school,
                    'request' => $request,
                    'original_portions' => (int) $request->requested_portions,
                    'revised_portions' => (int) $request->revised_portions,
                    'reason' => $request->revision_reason,
                    'requested_at' => $request->revision_requested_at,
                    'pic_name' => $school->picUsers->first()?->name ?? 'PIC Terdaftar',
                ];
            } else {
                // CONFIRMED or LOCKED
                $hasException = $request->hasUnapprovedException();
                if ($hasException) {
                    $unapprovedExceptions[] = [
                        'school' => $school,
                        'request' => $request,
                        'requested_portions' => (int) $request->requested_portions,
                        'registered_count' => (int) $request->registered_count,
                        'reason' => $request->revision_reason,
                    ];
                }

                $confirmedSchools[] = [
                    'school' => $school,
                    'request' => $request,
                    'requested_portions' => (int) $request->requested_portions,
                    'present_count' => $request->present_count,
                    'status' => $request->status,
                    'confirmed_by' => $request->confirmedBy?->name ?? 'PIC Terverifikasi',
                    'confirmed_at' => $request->confirmed_at,
                    'locked_at' => $request->locked_at,
                    'has_unapproved_exception' => $hasException,
                ];

                $totalConfirmedPortions += (int) $request->requested_portions;
            }
        }

        return [
            'service_date' => $serviceDate,
            'total_schools' => $schools->count(),
            'confirmed_count' => count($confirmedSchools),
            'revised_count' => count($revisedSchools),
            'pending_count' => count($pendingSchools),
            'cancelled_count' => count($cancelledSchools),
            'exception_count' => count($unapprovedExceptions),
            'total_confirmed_portions' => $totalConfirmedPortions,
            'all_confirmed' => (count($pendingSchools) === 0 && count($revisedSchools) === 0 && count($unapprovedExceptions) === 0),
            'confirmed_schools' => $confirmedSchools,
            'revised_schools' => $revisedSchools,
            'pending_schools' => $pendingSchools,
            'cancelled_schools' => $cancelledSchools,
            'unapproved_exceptions' => $unapprovedExceptions,
        ];
    }
}

