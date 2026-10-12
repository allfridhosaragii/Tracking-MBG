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
     * Get existing request or initialize a new draft for the given school and service date.
     */
    public function getOrCreateForDate(School $school, string $serviceDate, User $actor): DailyPortionRequest
    {
        return DB::transaction(function () use ($school, $serviceDate, $actor) {
            $request = DailyPortionRequest::where('school_id', $school->id)
                ->whereDate('service_date', $serviceDate)
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
     */
    public function saveDraft(School $school, string $serviceDate, array $data, User $actor): DailyPortionRequest
    {
        return DB::transaction(function () use ($school, $serviceDate, $data, $actor) {
            $portionRequest = $this->getOrCreateForDate($school, $serviceDate, $actor);

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
                event: 'portion_request.draft_saved',
                auditable: $portionRequest,
                actor: $actor,
                oldValues: $oldValues,
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

            if (! $locked->isDraft()) {
                throw ValidationException::withMessages([
                    'status' => ['Hanya permintaan berstatus Draft yang dapat dikonfirmasi.'],
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

            // Exceeding registered beneficiaries rule
            $reason = isset($data['revision_reason']) ? trim((string) $data['revision_reason']) : null;
            if ($requested > $locked->registered_count && empty($reason)) {
                throw ValidationException::withMessages([
                    'revision_reason' => ['Jumlah porsi yang diminta (' . number_format($requested) . ') melebihi jumlah siswa terdaftar (' . number_format($locked->registered_count) . '). Alasan justifikasi wajib disertakan.'],
                ]);
            }

            $locked->update([
                'requested_portions' => $requested,
                'present_count' => $present,
                'status' => PortionRequestStatus::CONFIRMED,
                'confirmed_by' => $pic->id,
                'confirmed_at' => now(),
                'revision_reason' => $reason,
            ]);

            ActivityLog::record(
                event: 'portion_request.confirmed',
                auditable: $locked,
                actor: $pic,
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
     * Submit an authorized revision for a confirmed or locked portion request.
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

            $requested = (int) $data['requested_portions'];
            if ($requested < 0) {
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
                'requested_portions' => $requested,
                'present_count' => $present,
                'status' => PortionRequestStatus::REVISED,
                'revision_reason' => $reason,
            ]);

            ActivityLog::record(
                event: 'portion_request.revised',
                auditable: $locked,
                actor: $actor,
                oldValues: $oldValues,
                newValues: [
                    'requested_portions' => $requested,
                    'present_count' => $present,
                    'status' => PortionRequestStatus::REVISED->value,
                    'revision_reason' => $reason,
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
     * CRITICAL RULE: Pending schools are NOT assumed to be 0!
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
        $pendingSchools = [];
        $cancelledSchools = [];
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
            } else {
                // CONFIRMED, LOCKED, or REVISED
                $confirmedSchools[] = [
                    'school' => $school,
                    'request' => $request,
                    'requested_portions' => (int) $request->requested_portions,
                    'present_count' => $request->present_count,
                    'status' => $request->status,
                    'confirmed_by' => $request->confirmedBy?->name ?? 'PIC Terverifikasi',
                    'confirmed_at' => $request->confirmed_at,
                ];
                $totalConfirmedPortions += (int) $request->requested_portions;
            }
        }

        return [
            'service_date' => $serviceDate,
            'total_schools' => $schools->count(),
            'confirmed_count' => count($confirmedSchools),
            'pending_count' => count($pendingSchools),
            'cancelled_count' => count($cancelledSchools),
            'total_confirmed_portions' => $totalConfirmedPortions,
            'confirmed_schools' => $confirmedSchools,
            'pending_schools' => $pendingSchools,
            'cancelled_schools' => $cancelledSchools,
        ];
    }
}
