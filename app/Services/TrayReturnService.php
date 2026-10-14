<?php

namespace App\Services;

use App\Enums\IncidentCategory;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Enums\TrayReturnStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\EvidencePhoto;
use App\Models\Incident;
use App\Models\SchoolServiceRecord;
use App\Models\TrayReturn;
use App\Models\TripStop;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TrayReturnService
{
    /**
     * Initialize or fetch the TrayReturn tracking record for a SchoolServiceRecord.
     */
    public function initiateForRecord(SchoolServiceRecord $record, ?int $expectedTrays = null): TrayReturn
    {
        return DB::transaction(function () use ($record, $expectedTrays) {
            $lockedRecord = SchoolServiceRecord::where('id', $record->id)
                ->with('tripStop')
                ->lockForUpdate()
                ->firstOrFail();

            $existing = TrayReturn::where('school_service_record_id', $lockedRecord->id)->first();
            if ($existing) {
                return $existing;
            }

            // Expected trays defaults to physical portions received, or expected portions
            $trays = $expectedTrays ?? (int) ($lockedRecord->received_portions ?? $lockedRecord->expected_portions);
            if ($trays <= 0) {
                $trays = 1;
            }

            return TrayReturn::create([
                'school_service_record_id' => $lockedRecord->id,
                'expected_trays' => $trays,
                'status' => TrayReturnStatus::IN_USE,
            ]);
        });
    }

    /**
     * PIC Sekolah declares containers ready for pickup (Page P06 /school/trays).
     *
     * Transitions status from IN_USE to READY_FOR_PICKUP (or DISCREPANCY).
     * Validates physical ready counts, records server timestamp, and logs activity.
     */
    public function markReady(TrayReturn $trayReturn, User $pic, array $data, ?UploadedFile $photo = null): TrayReturn
    {
        $uploadedPaths = [];

        try {
            return DB::transaction(function () use ($trayReturn, $pic, $data, $photo, &$uploadedPaths) {
                $lockedReturn = TrayReturn::where('id', $trayReturn->id)
                    ->with(['schoolServiceRecord.tripStop.school', 'schoolServiceRecord.tripStop.trip'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $school = $lockedReturn->school;
                $sppg = $lockedReturn->sppg;

                // 1. Authorization: Only PIC assigned to this school can mark trays ready
                if ($pic->role !== UserRole::PIC_SEKOLAH || (int) $pic->school_id !== (int) $school?->id) {
                    throw new HttpException(403, 'Akses ditolak. Anda bukan PIC yang ditugaskan untuk sekolah ini.');
                }

                // 2. Precondition: Status must be IN_USE
                if ($lockedReturn->status !== TrayReturnStatus::IN_USE) {
                    // Idempotency: If already READY_FOR_PICKUP with identical counts, return safely
                    if ($lockedReturn->status === TrayReturnStatus::READY_FOR_PICKUP &&
                        $lockedReturn->ready_count === (int) ($data['ready_count'] ?? 0)) {
                        return $lockedReturn;
                    }

                    throw new ConflictHttpException(
                        "Status ompreng telah berstatus {$lockedReturn->status->label()}. Tindakan penandaan siap jemput tidak diizinkan."
                    );
                }

                // 3. Count Validation (Gate 3: Physical Count Integrity)
                if (!isset($data['ready_count']) || $data['ready_count'] === '' || !is_numeric($data['ready_count'])) {
                    throw ValidationException::withMessages([
                        'ready_count' => ['Jumlah ompreng siap diambil wajib diisi dengan bilangan bulat non-negatif.'],
                    ]);
                }

                $readyCount = (int) $data['ready_count'];
                if ($readyCount < 0) {
                    throw ValidationException::withMessages([
                        'ready_count' => ['Jumlah ompreng siap diambil tidak boleh bernilai negatif.'],
                    ]);
                }

                $damagedCount = 0;
                if (isset($data['damaged_count']) && $data['damaged_count'] !== '') {
                    $damagedCount = (int) $data['damaged_count'];
                    if ($damagedCount < 0) {
                        throw ValidationException::withMessages([
                            'damaged_count' => ['Jumlah ompreng rusak tidak boleh bernilai negatif.'],
                        ]);
                    }
                }

                $notes = isset($data['notes']) ? trim((string) $data['notes']) : (isset($data['discrepancy_reason']) ? trim((string) $data['discrepancy_reason']) : null);
                $hasDiscrepancy = ($readyCount < $lockedReturn->expected_trays) || ($damagedCount > 0);

                // Discrepancy requires explanation notes
                if ($hasDiscrepancy && empty($notes)) {
                    throw ValidationException::withMessages([
                        'notes' => ['Catatan penjelasan wajib diisi jika terdapat wadah yang kurang dari alokasi awal atau ditemukan wadah rusak.'],
                    ]);
                }

                $now = Carbon::now();
                $lockedReturn->ready_count = $readyCount;
                $lockedReturn->damaged_count = $damagedCount;
                $lockedReturn->ready_at = $now;
                $lockedReturn->discrepancy_reason = $notes;
                $lockedReturn->status = TrayReturnStatus::READY_FOR_PICKUP;
                $lockedReturn->save();

                // If evidence photo is provided, store securely
                if ($photo) {
                    $this->storeEvidencePhoto($lockedReturn, $pic, $photo, 'TRAYS_READY', $notes, $uploadedPaths);
                }

                // If discrepancy detected, log Incident for SPPG management
                if ($hasDiscrepancy) {
                    $missing = max(0, $lockedReturn->expected_trays - $readyCount);
                    $severity = ($missing > 10 || $damagedCount > 10) ? IncidentSeverity::HIGH : IncidentSeverity::MEDIUM;
                    
                    Incident::create([
                        'sppg_id' => $sppg->id,
                        'school_id' => $school->id,
                        'subject_type' => TrayReturn::class,
                        'subject_id' => $lockedReturn->id,
                        'reported_by' => $pic->id,
                        'category' => IncidentCategory::TRAY_DISCREPANCY,
                        'severity' => $severity,
                        'status' => IncidentStatus::OPEN,
                        'description' => "Selisih kesiapan wadah di {$school->name}: siap {$readyCount} dari {$lockedReturn->expected_trays} wadah diharapkan. Kurang: {$missing}, Rusak: {$damagedCount}." . ($notes ? " Catatan: {$notes}" : ''),
                    ]);
                }

                ActivityLog::record(
                    event: 'tray_return.marked_ready',
                    auditable: $lockedReturn,
                    actor: $pic,
                    newValues: [
                        'status' => TrayReturnStatus::READY_FOR_PICKUP->value,
                        'ready_count' => $readyCount,
                        'damaged_count' => $damagedCount,
                        'ready_at' => $now->toIso8601String(),
                        'has_discrepancy' => $hasDiscrepancy,
                    ]
                );

                return $lockedReturn;
            });
        } catch (\Throwable $e) {
            foreach ($uploadedPaths as $path) {
                if ($path && Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                }
            }
            throw $e;
        }
    }

    /**
     * Assign a courier/trip stop to pick up ready containers.
     */
    public function assignPickup(TrayReturn $trayReturn, User $actor, TripStop $pickupStop): TrayReturn
    {
        return DB::transaction(function () use ($trayReturn, $actor, $pickupStop) {
            $lockedReturn = TrayReturn::where('id', $trayReturn->id)
                ->with(['schoolServiceRecord.tripStop.school'])
                ->lockForUpdate()
                ->firstOrFail();

            $sppg = $lockedReturn->sppg;

            // Authorization: Kepala SPPG within the same SPPG
            if ($actor->role !== UserRole::KEPALA_SPPG || (int) $actor->sppg_id !== (int) $sppg?->id) {
                throw new HttpException(403, 'Akses ditolak. Penugasan penjemputan hanya dapat dilakukan oleh Kepala SPPG.');
            }

            if ($lockedReturn->status !== TrayReturnStatus::READY_FOR_PICKUP) {
                throw new ConflictHttpException(
                    "Penugasan penjemputan hanya dapat dilakukan saat wadah berstatus Siap Dijemput (READY_FOR_PICKUP). Status saat ini: {$lockedReturn->status->label()}."
                );
            }

            if ((int) $pickupStop->school_id !== (int) $lockedReturn->school?->id) {
                throw ValidationException::withMessages([
                    'stop' => ['Pemberhentian penjemputan tidak sesuai dengan sekolah wadah ini.'],
                ]);
            }

            $lockedReturn->pickup_trip_stop_id = $pickupStop->id;
            $lockedReturn->status = TrayReturnStatus::PICKUP_ASSIGNED;
            $lockedReturn->save();

            ActivityLog::record(
                event: 'tray_return.pickup_assigned',
                auditable: $lockedReturn,
                actor: $actor,
                newValues: [
                    'pickup_trip_stop_id' => $pickupStop->id,
                    'status' => TrayReturnStatus::PICKUP_ASSIGNED->value,
                ]
            );

            return $lockedReturn;
        });
    }

    /**
     * Courier records physical container pickup at school (Page D05 /driver/pickups).
     *
     * Transitions status to PICKED_UP. Records picked_up_count and picked_up_at.
     * Enforces assigned courier authorization and preserves discrepancy evidence.
     */
    public function recordPickup(TrayReturn $trayReturn, User $driver, array $data, ?UploadedFile $photo = null): TrayReturn
    {
        $uploadedPaths = [];

        try {
            return DB::transaction(function () use ($trayReturn, $driver, $data, $photo, &$uploadedPaths) {
                $lockedReturn = TrayReturn::where('id', $trayReturn->id)
                    ->with(['schoolServiceRecord.tripStop.school', 'pickupTripStop.trip'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $school = $lockedReturn->school;
                $sppg = $lockedReturn->sppg;

                // 1. Authorization: Courier must belong to same SPPG
                if ($driver->role !== UserRole::KURIR || (int) $driver->sppg_id !== (int) $sppg?->id) {
                    throw new HttpException(403, 'Akses ditolak. Hanya kurir pada SPPG terkait yang dapat mencatat pengambilan.');
                }

                // If assigned to a specific pickup trip stop, must be the assigned driver
                if ($lockedReturn->pickup_trip_stop_id && $lockedReturn->pickupTripStop?->trip) {
                    if ((int) $lockedReturn->pickupTripStop->trip->driver_id !== (int) $driver->id) {
                        throw new HttpException(403, 'Akses ditolak. Anda bukan kurir yang ditugaskan untuk penjemputan ini.');
                    }
                }

                // 2. Precondition: Cannot pick up if still IN_USE
                if ($lockedReturn->status === TrayReturnStatus::IN_USE) {
                    throw new ConflictHttpException(
                        'Wadah belum dinyatakan siap diambil oleh PIC Sekolah (masih berstatus Dalam Penggunaan).'
                    );
                }

                // Idempotency: If already PICKED_UP with same count, return safely
                if ($lockedReturn->status === TrayReturnStatus::PICKED_UP &&
                    $lockedReturn->picked_up_count === (int) ($data['picked_up_count'] ?? 0)) {
                    return $lockedReturn;
                }

                if ($lockedReturn->status === TrayReturnStatus::RETURNED_TO_SPPG) {
                    throw new ConflictHttpException('Wadah telah dikembalikan ke SPPG. Pengambilan tidak dapat diubah.');
                }

                // 3. Count Validation
                if (!isset($data['picked_up_count']) || $data['picked_up_count'] === '' || !is_numeric($data['picked_up_count'])) {
                    throw ValidationException::withMessages([
                        'picked_up_count' => ['Jumlah wadah yang diambil wajib diisi dengan angka bulat non-negatif.'],
                    ]);
                }

                $pickedUpCount = (int) $data['picked_up_count'];
                if ($pickedUpCount < 0) {
                    throw ValidationException::withMessages([
                        'picked_up_count' => ['Jumlah wadah yang diambil tidak boleh bernilai negatif.'],
                    ]);
                }

                $notes = isset($data['notes']) ? trim((string) $data['notes']) : (isset($data['discrepancy_reason']) ? trim((string) $data['discrepancy_reason']) : null);
                $hasDiscrepancy = ($pickedUpCount !== $lockedReturn->ready_count);

                if ($hasDiscrepancy && empty($notes)) {
                    throw ValidationException::withMessages([
                        'notes' => ['Catatan penjelasan wajib diisi jika jumlah wadah yang diambil berbeda dari jumlah siap yang dicatat PIC sekolah.'],
                    ]);
                }

                $now = Carbon::now();
                $lockedReturn->picked_up_count = $pickedUpCount;
                $lockedReturn->picked_up_at = $now;
                $lockedReturn->status = $hasDiscrepancy ? TrayReturnStatus::DISCREPANCY : TrayReturnStatus::PICKED_UP;

                if ($notes) {
                    $lockedReturn->discrepancy_reason = $lockedReturn->discrepancy_reason
                        ? $lockedReturn->discrepancy_reason . " | Kurir: {$notes}"
                        : "Kurir: {$notes}";
                }

                $lockedReturn->save();

                // Store pickup photo
                if ($photo) {
                    $this->storeEvidencePhoto($lockedReturn, $driver, $photo, 'TRAYS_PICKED_UP', $notes, $uploadedPaths);
                }

                // If pickup discrepancy exists, log/update incident
                if ($hasDiscrepancy) {
                    Incident::create([
                        'sppg_id' => $sppg->id,
                        'school_id' => $school->id,
                        'subject_type' => TrayReturn::class,
                        'subject_id' => $lockedReturn->id,
                        'reported_by' => $driver->id,
                        'category' => IncidentCategory::TRAY_DISCREPANCY,
                        'severity' => IncidentSeverity::MEDIUM,
                        'status' => IncidentStatus::OPEN,
                        'description' => "Selisih pengambilan ompreng oleh Kurir di {$school->name}: Diambil {$pickedUpCount} wadah vs {$lockedReturn->ready_count} wadah siap tercatat." . ($notes ? " Catatan: {$notes}" : ''),
                    ]);
                }

                ActivityLog::record(
                    event: 'tray_return.picked_up',
                    auditable: $lockedReturn,
                    actor: $driver,
                    newValues: [
                        'status' => TrayReturnStatus::PICKED_UP->value,
                        'picked_up_count' => $pickedUpCount,
                        'picked_up_at' => $now->toIso8601String(),
                        'has_discrepancy' => $hasDiscrepancy,
                    ]
                );

                return $lockedReturn;
            });
        } catch (\Throwable $e) {
            foreach ($uploadedPaths as $path) {
                if ($path && Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                }
            }
            throw $e;
        }
    }

    /**
     * Depot reception by Asisten Lapangan / SPPG staff (Page A03 /production/logistics).
     *
     * Transitions status to RETURNED_TO_SPPG or DISCREPANCY.
     * Compares returned_count, damaged_count, missing_count against picked_up_count.
     * Does NOT overwrite picked_up_count. Preserves provenance.
     */
    public function receiveAtDepot(TrayReturn $trayReturn, User $actor, array $data, ?UploadedFile $photo = null): TrayReturn
    {
        $uploadedPaths = [];

        try {
            return DB::transaction(function () use ($trayReturn, $actor, $data, $photo, &$uploadedPaths) {
                $lockedReturn = TrayReturn::where('id', $trayReturn->id)
                    ->with(['schoolServiceRecord.tripStop.school'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $school = $lockedReturn->school;
                $sppg = $lockedReturn->sppg;

                // 1. Authorization: Asisten Lapangan or Kepala SPPG in same SPPG
                if (!in_array($actor->role, [UserRole::ASISTEN_LAPANGAN, UserRole::KEPALA_SPPG], true) ||
                    (int) $actor->sppg_id !== (int) $sppg?->id) {
                    throw new HttpException(403, 'Akses ditolak. Penerimaan retur wadah di SPPG hanya dapat dilakukan oleh Asisten Lapangan atau Kepala SPPG unit terkait.');
                }

                // 2. Precondition: Must be PICKED_UP
                if ($lockedReturn->status !== TrayReturnStatus::PICKED_UP && $lockedReturn->status !== TrayReturnStatus::DISCREPANCY) {
                    if ($lockedReturn->status === TrayReturnStatus::RETURNED_TO_SPPG) {
                        return $lockedReturn; // Idempotent
                    }

                    throw new ConflictHttpException(
                        "Penerimaan retur di SPPG hanya dapat diproses setelah kurir mencatat pengambilan fisik (PICKED_UP). Status saat ini: {$lockedReturn->status->label()}."
                    );
                }

                // 3. Count Validation
                if (!isset($data['returned_count']) || $data['returned_count'] === '' || !is_numeric($data['returned_count'])) {
                    throw ValidationException::withMessages([
                        'returned_count' => ['Jumlah wadah fisik yang diterima di SPPG wajib diisi dengan angka bulat non-negatif.'],
                    ]);
                }

                $returnedCount = (int) $data['returned_count'];
                if ($returnedCount < 0) {
                    throw ValidationException::withMessages([
                        'returned_count' => ['Jumlah wadah diterima tidak boleh bernilai negatif.'],
                    ]);
                }

                $damagedCount = isset($data['damaged_count']) ? (int) $data['damaged_count'] : 0;
                if ($damagedCount < 0) {
                    throw ValidationException::withMessages([
                        'damaged_count' => ['Jumlah wadah rusak tidak boleh bernilai negatif.'],
                    ]);
                }

                $missingCount = isset($data['missing_count']) ? (int) $data['missing_count'] : 0;
                if ($missingCount < 0) {
                    throw ValidationException::withMessages([
                        'missing_count' => ['Jumlah wadah hilang tidak boleh bernilai negatif.'],
                    ]);
                }

                $notes = isset($data['notes']) ? trim((string) $data['notes']) : (isset($data['discrepancy_reason']) ? trim((string) $data['discrepancy_reason']) : null);
                
                // Compare returned counts with picked_up_count
                $expectedAtDepot = (int) ($lockedReturn->picked_up_count ?? $lockedReturn->ready_count ?? $lockedReturn->expected_trays);
                $totalAccounted = $returnedCount + $damagedCount;
                $hasDiscrepancy = ($totalAccounted !== $expectedAtDepot) || ($missingCount > 0);

                if ($hasDiscrepancy && empty($notes)) {
                    throw ValidationException::withMessages([
                        'notes' => ['Catatan penjelasan wajib diisi jika terdapat selisih jumlah wadah kembali (kurang/rusak/hilang).'],
                    ]);
                }

                $now = Carbon::now();
                $lockedReturn->returned_count = $returnedCount;
                $lockedReturn->damaged_count = $damagedCount;
                $lockedReturn->missing_count = $missingCount;
                $lockedReturn->returned_at = $now;
                $lockedReturn->status = $hasDiscrepancy ? TrayReturnStatus::DISCREPANCY : TrayReturnStatus::RETURNED_TO_SPPG;

                if ($notes) {
                    $lockedReturn->discrepancy_reason = $lockedReturn->discrepancy_reason
                        ? $lockedReturn->discrepancy_reason . " | Depot: {$notes}"
                        : "Depot: {$notes}";
                }

                $lockedReturn->save();

                if ($photo) {
                    $this->storeEvidencePhoto($lockedReturn, $actor, $photo, 'TRAYS_RETURNED', $notes, $uploadedPaths);
                }

                // If depot discrepancy, create/deduplicate Incident
                if ($hasDiscrepancy) {
                    $discrepancySum = abs($expectedAtDepot - $returnedCount) + $damagedCount + $missingCount;
                    $severity = ($discrepancySum > 10) ? IncidentSeverity::HIGH : IncidentSeverity::MEDIUM;

                    Incident::create([
                        'sppg_id' => $sppg->id,
                        'school_id' => $school->id,
                        'subject_type' => TrayReturn::class,
                        'subject_id' => $lockedReturn->id,
                        'reported_by' => $actor->id,
                        'category' => IncidentCategory::TRAY_DISCREPANCY,
                        'severity' => $severity,
                        'status' => IncidentStatus::OPEN,
                        'description' => "Selisih retur wadah di depot SPPG dari {$school->name}: diterima {$returnedCount} dari {$expectedAtDepot} diambil. Rusak: {$damagedCount}, Hilang: {$missingCount}." . ($notes ? " Catatan: {$notes}" : ''),
                    ]);
                }

                ActivityLog::record(
                    event: 'tray_return.received_at_depot',
                    auditable: $lockedReturn,
                    actor: $actor,
                    newValues: [
                        'status' => $lockedReturn->status->value,
                        'returned_count' => $returnedCount,
                        'damaged_count' => $damagedCount,
                        'missing_count' => $missingCount,
                        'returned_at' => $now->toIso8601String(),
                        'has_discrepancy' => $hasDiscrepancy,
                    ]
                );

                return $lockedReturn;
            });
        } catch (\Throwable $e) {
            foreach ($uploadedPaths as $path) {
                if ($path && Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                }
            }
            throw $e;
        }
    }

    /**
     * Store evidence photo attached to a TrayReturn.
     */
    protected function storeEvidencePhoto(TrayReturn $trayReturn, User $uploader, UploadedFile $file, string $type, ?string $notes = null, array &$uploadedPaths = []): EvidencePhoto
    {
        $path = $file->store('evidence_photos/trays', 'local');
        $uploadedPaths[] = $path;

        return EvidencePhoto::create([
            'evidenceable_type' => TrayReturn::class,
            'evidenceable_id' => $trayReturn->id,
            'evidence_type' => $type,
            'uploaded_by' => $uploader->id,
            'file_path' => $path,
            'mime_type' => $file->getClientMimeType() ?: 'image/jpeg',
            'file_size' => $file->getSize() ?: 0,
            'captured_at' => Carbon::now(),
            'notes' => $notes,
        ]);
    }
}
