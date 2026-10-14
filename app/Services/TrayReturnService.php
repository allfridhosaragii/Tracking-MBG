<?php

namespace App\Services;

use App\Enums\IncidentCategory;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Enums\TrayReturnStatus;
use App\Enums\TripStatus;
use App\Enums\TripStopStatus;
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
     *
     * GATE A: Container count is physically distinct from meal portions.
     * If no verified manifest is provided, expected_trays remains null and unverified.
     * If verified expectedTrays is 0, it remains 0 (not forced to 1).
     */
    public function initiateForRecord(
        SchoolServiceRecord $record,
        ?int $expectedTrays = null,
        bool $isVerified = false,
        ?string $manifestReference = null
    ): TrayReturn {
        return DB::transaction(function () use ($record, $expectedTrays, $isVerified, $manifestReference) {
            $lockedRecord = SchoolServiceRecord::where('id', $record->id)
                ->with('tripStop')
                ->lockForUpdate()
                ->firstOrFail();

            $existing = TrayReturn::where('school_service_record_id', $lockedRecord->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            // Strict physical count provenance:
            // Do NOT substitute meal portions (planned or received) for physical container count.
            // When expectedTrays is 0, preserve 0 (do NOT force to 1).
            $trays = null;
            $verified = false;

            if ($expectedTrays !== null) {
                $trays = (int) $expectedTrays;
                $verified = $isVerified;
            }

            return TrayReturn::create([
                'school_service_record_id' => $lockedRecord->id,
                'expected_trays' => $trays,
                'is_expected_verified' => $verified,
                'manifest_reference' => $manifestReference,
                'status' => TrayReturnStatus::IN_USE,
            ]);
        });
    }

    /**
     * PIC Sekolah declares containers ready for pickup (Page P06 /school/trays).
     *
     * Transitions status from IN_USE to READY_FOR_PICKUP.
     * GATE A & E: Does NOT trigger missing incident against unverified/unknown expected baseline.
     * Idempotent on identical retry without inflating incidents or logs.
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

                // 2. Count Validation
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

                // 3. Precondition & Idempotency Check (GATE E)
                if ($lockedReturn->status !== TrayReturnStatus::IN_USE || $lockedReturn->ready_at !== null) {
                    if ((int) $lockedReturn->ready_count === $readyCount &&
                        (int) ($lockedReturn->damaged_count ?? 0) === $damagedCount) {
                        // Identical retry: return existing record safely without mutating timestamps, logs, or incidents
                        return $lockedReturn;
                    }

                    throw new ConflictHttpException(
                        "Status ompreng telah berstatus {$lockedReturn->status->label()}. Tindakan penandaan siap jemput tidak diizinkan."
                    );
                }

                $notes = isset($data['notes']) ? trim((string) $data['notes']) : (isset($data['discrepancy_reason']) ? trim((string) $data['discrepancy_reason']) : null);

                // GATE A: Physical Discrepancy against verified baseline only.
                // If expected_trays is unknown/unverified (e.g. from food portions), do NOT fabricate a missing incident.
                $isMissingFromExpected = ($lockedReturn->is_expected_verified && $lockedReturn->expected_trays !== null && $readyCount < $lockedReturn->expected_trays);
                $hasDamaged = ($damagedCount > 0);
                $hasDiscrepancy = $isMissingFromExpected || $hasDamaged;

                // Discrepancy requires explanation notes
                if ($hasDiscrepancy && empty($notes)) {
                    throw ValidationException::withMessages([
                        'notes' => ['Catatan penjelasan wajib diisi jika terdapat wadah yang kurang dari alokasi terverifikasi atau ditemukan wadah rusak.'],
                    ]);
                }

                $now = Carbon::now();
                $lockedReturn->ready_count = $readyCount;
                $lockedReturn->damaged_count = $damagedCount;
                if ($lockedReturn->ready_at === null) {
                    $lockedReturn->ready_at = $now;
                }
                $lockedReturn->discrepancy_reason = $notes;
                $lockedReturn->status = TrayReturnStatus::READY_FOR_PICKUP;
                $lockedReturn->save();

                // If evidence photo is provided, store securely
                if ($photo) {
                    $this->storeEvidencePhoto($lockedReturn, $pic, $photo, 'TRAYS_READY', $notes, $uploadedPaths);
                }

                // GATE E: Deduplicate Incident. Check for existing OPEN incident for this return by PIC
                if ($hasDiscrepancy) {
                    $missing = ($lockedReturn->is_expected_verified && $lockedReturn->expected_trays !== null)
                        ? max(0, $lockedReturn->expected_trays - $readyCount)
                        : 0;
                    $severity = ($missing > 10 || $damagedCount > 10) ? IncidentSeverity::HIGH : IncidentSeverity::MEDIUM;
                    $desc = "Kesiapan wadah di {$school->name}: Siap {$readyCount}" .
                        ($lockedReturn->is_expected_verified ? " dari {$lockedReturn->expected_trays} wadah terverifikasi (Kurang: {$missing})" : '') .
                        ", Rusak: {$damagedCount}." . ($notes ? " Catatan: {$notes}" : '');

                    $existingIncident = Incident::where('subject_type', TrayReturn::class)
                        ->where('subject_id', $lockedReturn->id)
                        ->where('category', IncidentCategory::TRAY_DISCREPANCY)
                        ->where('status', IncidentStatus::OPEN)
                        ->where('reported_by', $pic->id)
                        ->first();

                    if ($existingIncident) {
                        $existingIncident->update([
                            'description' => $desc,
                            'severity' => $severity,
                        ]);
                    } else {
                        Incident::create([
                            'sppg_id' => $sppg->id,
                            'school_id' => $school->id,
                            'subject_type' => TrayReturn::class,
                            'subject_id' => $lockedReturn->id,
                            'reported_by' => $pic->id,
                            'category' => IncidentCategory::TRAY_DISCREPANCY,
                            'severity' => $severity,
                            'status' => IncidentStatus::OPEN,
                            'description' => $desc,
                        ]);
                    }
                }

                ActivityLog::record(
                    event: 'tray_return.marked_ready',
                    auditable: $lockedReturn,
                    actor: $pic,
                    newValues: [
                        'status' => TrayReturnStatus::READY_FOR_PICKUP->value,
                        'ready_count' => $readyCount,
                        'damaged_count' => $damagedCount,
                        'ready_at' => $lockedReturn->ready_at->toIso8601String(),
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
     * GATE B: Official Pickup Assignment by Kepala SPPG.
     *
     * Validates pickup trip, pickup stop, school_id, sppg_id, trip status, and driver_id.
     * Rejects foreign tenant stops, completed/cancelled trips, and mismatched schools.
     */
    public function assignPickup(TrayReturn $trayReturn, User $actor, TripStop $pickupStop): TrayReturn
    {
        return DB::transaction(function () use ($trayReturn, $actor, $pickupStop) {
            $lockedReturn = TrayReturn::where('id', $trayReturn->id)
                ->with(['schoolServiceRecord.tripStop.school', 'schoolServiceRecord.tripStop.trip'])
                ->lockForUpdate()
                ->firstOrFail();

            $sppg = $lockedReturn->sppg;

            // 1. Authorization: Only Kepala SPPG within the same SPPG
            if ($actor->role !== UserRole::KEPALA_SPPG || (int) $actor->sppg_id !== (int) $sppg?->id) {
                throw new HttpException(403, 'Akses ditolak. Penugasan penjemputan hanya dapat dilakukan oleh Kepala SPPG unit terkait.');
            }

            // 2. Precondition: Must be READY_FOR_PICKUP (or PICKUP_ASSIGNED for reassignment before pickup)
            if ($lockedReturn->status !== TrayReturnStatus::READY_FOR_PICKUP && $lockedReturn->status !== TrayReturnStatus::PICKUP_ASSIGNED) {
                throw new ConflictHttpException(
                    "Penugasan penjemputan hanya dapat dilakukan saat wadah berstatus Siap Dijemput (READY_FOR_PICKUP). Status saat ini: {$lockedReturn->status->label()}."
                );
            }

            // 3. Stop school verification: Stop must match the exact school of the trays
            if ((int) $pickupStop->school_id !== (int) $lockedReturn->school_id) {
                throw ValidationException::withMessages([
                    'stop' => ['Pemberhentian penjemputan tidak sesuai dengan sekolah wadah ini.'],
                ]);
            }

            // 4. Trip tenant verification: Stop's trip must belong to the same SPPG
            $trip = $pickupStop->trip;
            if (!$trip || (int) $trip->sppg_id !== (int) $sppg?->id) {
                throw new HttpException(403, 'Akses ditolak. Rute perjalanan penjemputan berasal dari unit SPPG lain.');
            }

            // 5. Trip status check: Must NOT be completed or cancelled
            if (in_array($trip->status, [TripStatus::COMPLETED, TripStatus::CANCELLED], true)) {
                throw ValidationException::withMessages([
                    'trip' => ['Tidak dapat menugaskan penjemputan pada perjalanan pengiriman yang telah selesai atau dibatalkan.'],
                ]);
            }

            // 6. Stop status check: Stop must not be skipped or completed
            if (in_array($pickupStop->status, [TripStopStatus::SKIPPED, TripStopStatus::COMPLETED], true)) {
                throw ValidationException::withMessages([
                    'stop' => ['Pemberhentian penjemputan berada dalam status yang tidak sah untuk penugasan penjemputan aktif.'],
                ]);
            }

            // 7. Driver assigned check
            if (empty($trip->driver_id)) {
                throw ValidationException::withMessages([
                    'driver' => ['Perjalanan penjemputan belum memiliki kurir yang ditugaskan.'],
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
                    'trip_id' => $trip->id,
                    'driver_id' => $trip->driver_id,
                    'status' => TrayReturnStatus::PICKUP_ASSIGNED->value,
                ]
            );

            return $lockedReturn;
        });
    }

    /**
     * Courier records physical container pickup at school (Page D05 /driver/pickups).
     *
     * GATE B: Enforces strict assignment (READY_FOR_PICKUP without assignment is 403).
     * GATE E: Idempotent on identical retry without inflating incidents or updating timestamps.
     * Preserves custody and deduplicates incidents.
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

                // GATE B: Official Pickup Assignment Required!
                // READY_FOR_PICKUP without official assignment does NOT give pickup authority to any courier.
                if (!$lockedReturn->pickup_trip_stop_id || !$lockedReturn->pickupTripStop?->trip) {
                    throw new HttpException(403, 'Akses ditolak. Wadah belum ditugaskan secara resmi kepada kurir penjemput.');
                }

                // Courier must match the assigned driver
                $assignedDriverId = (int) $lockedReturn->pickupTripStop->trip->driver_id;
                if ($assignedDriverId !== (int) $driver->id) {
                    throw new HttpException(403, 'Akses ditolak. Anda bukan kurir yang ditugaskan untuk penjemputan ini.');
                }

                // 2. Count Validation
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

                // 3. Precondition & Idempotency Check (GATE E)
                if ($lockedReturn->picked_up_at !== null) {
                    if ((int) $lockedReturn->picked_up_count === $pickedUpCount) {
                        // Identical retry: return existing record without updating timestamps, logs, or duplicate incidents
                        return $lockedReturn;
                    }

                    if ($lockedReturn->returned_at !== null) {
                        throw new ConflictHttpException('Wadah telah diproses di depot SPPG. Jumlah pengambilan tidak dapat diubah.');
                    }
                } elseif ($lockedReturn->status === TrayReturnStatus::IN_USE) {
                    throw new ConflictHttpException(
                        'Wadah belum dinyatakan siap diambil oleh PIC Sekolah (masih berstatus Dalam Penggunaan).'
                    );
                }

                $notes = isset($data['notes']) ? trim((string) $data['notes']) : (isset($data['discrepancy_reason']) ? trim((string) $data['discrepancy_reason']) : null);
                $hasDiscrepancy = ($pickedUpCount !== (int) $lockedReturn->ready_count);

                if ($hasDiscrepancy && empty($notes)) {
                    throw ValidationException::withMessages([
                        'notes' => ['Catatan penjelasan wajib diisi jika jumlah wadah yang diambil berbeda dari jumlah siap yang dicatat PIC sekolah.'],
                    ]);
                }

                $now = Carbon::now();
                $lockedReturn->picked_up_count = $pickedUpCount;
                if ($lockedReturn->picked_up_at === null) {
                    $lockedReturn->picked_up_at = $now;
                }
                $lockedReturn->status = $hasDiscrepancy ? TrayReturnStatus::DISCREPANCY : TrayReturnStatus::PICKED_UP;

                if ($notes) {
                    $lockedReturn->discrepancy_reason = $lockedReturn->discrepancy_reason
                        ? $lockedReturn->discrepancy_reason . " | Kurir: {$notes}"
                        : "Kurir: {$notes}";
                }

                $lockedReturn->save();

                // Store pickup photo if provided
                if ($photo) {
                    $this->storeEvidencePhoto($lockedReturn, $driver, $photo, 'TRAYS_PICKED_UP', $notes, $uploadedPaths);
                }

                // GATE E: Deduplicate Incident for Courier Pickup Discrepancy
                $existingIncident = Incident::where('subject_type', TrayReturn::class)
                    ->where('subject_id', $lockedReturn->id)
                    ->where('category', IncidentCategory::TRAY_DISCREPANCY)
                    ->where('status', IncidentStatus::OPEN)
                    ->where('reported_by', $driver->id)
                    ->first();

                if ($hasDiscrepancy) {
                    $desc = "Selisih pengambilan ompreng oleh Kurir di {$school->name}: Diambil {$pickedUpCount} wadah vs {$lockedReturn->ready_count} wadah siap tercatat." . ($notes ? " Catatan: {$notes}" : '');

                    if ($existingIncident) {
                        $existingIncident->update([
                            'description' => $desc,
                            'severity' => IncidentSeverity::MEDIUM,
                        ]);
                    } else {
                        Incident::create([
                            'sppg_id' => $sppg->id,
                            'school_id' => $school->id,
                            'subject_type' => TrayReturn::class,
                            'subject_id' => $lockedReturn->id,
                            'reported_by' => $driver->id,
                            'category' => IncidentCategory::TRAY_DISCREPANCY,
                            'severity' => IncidentSeverity::MEDIUM,
                            'status' => IncidentStatus::OPEN,
                            'description' => $desc,
                        ]);
                    }
                } elseif ($existingIncident) {
                    // Count was corrected to match ready count. Do NOT delete or silently close open incident!
                    $existingIncident->update([
                        'description' => $existingIncident->description . " | Koreksi Kurir: Jumlah diambil diperbarui menjadi {$pickedUpCount} (sesuai kesiapan). Menunggu peninjauan administratif Kepala SPPG.",
                    ]);
                }

                ActivityLog::record(
                    event: 'tray_return.picked_up',
                    auditable: $lockedReturn,
                    actor: $driver,
                    newValues: [
                        'status' => $lockedReturn->status->value,
                        'picked_up_count' => $pickedUpCount,
                        'picked_up_at' => $lockedReturn->picked_up_at->toIso8601String(),
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
     * GATE D: Discrepant pickups remain in incoming depot queue until physical reception.
     * GATE E: Idempotent on identical retry. Deduplicates depot incidents without double counting.
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

                // 2. Precondition: Must have been picked up physically (picked_up_at !== null)
                if ($lockedReturn->picked_up_at === null) {
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

                // 4. Precondition & Idempotency Check (GATE E)
                if ($lockedReturn->returned_at !== null) {
                    if ((int) $lockedReturn->returned_count === $returnedCount &&
                        (int) ($lockedReturn->damaged_count ?? 0) === $damagedCount &&
                        (int) ($lockedReturn->missing_count ?? 0) === $missingCount) {
                        // Identical retry: return existing record safely without modifying timestamp, activity logs or incidents
                        return $lockedReturn;
                    }
                }

                $notes = isset($data['notes']) ? trim((string) $data['notes']) : (isset($data['discrepancy_reason']) ? trim((string) $data['discrepancy_reason']) : null);

                // Compare returned counts against courier's picked_up_count
                $expectedAtDepot = (int) ($lockedReturn->picked_up_count ?? $lockedReturn->ready_count ?? $lockedReturn->expected_trays);
                $totalAccounted = $returnedCount + $damagedCount;
                $hasDiscrepancy = ($totalAccounted !== $expectedAtDepot) || ($missingCount > 0);

                if ($hasDiscrepancy && empty($notes)) {
                    throw ValidationException::withMessages([
                        'notes' => ['Catatan penjelasan wajib diisi jika terdapat selisih jumlah wadah kembali di depot (kurang/rusak/hilang).'],
                    ]);
                }

                $now = Carbon::now();
                $lockedReturn->returned_count = $returnedCount;
                $lockedReturn->damaged_count = $damagedCount;
                $lockedReturn->missing_count = $missingCount;
                if ($lockedReturn->returned_at === null) {
                    $lockedReturn->returned_at = $now;
                }
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

                // GATE E: Deduplicate Depot Incident
                $existingDepotIncident = Incident::where('subject_type', TrayReturn::class)
                    ->where('subject_id', $lockedReturn->id)
                    ->where('category', IncidentCategory::TRAY_DISCREPANCY)
                    ->where('status', IncidentStatus::OPEN)
                    ->where('reported_by', $actor->id)
                    ->first();

                if ($hasDiscrepancy) {
                    $discrepancySum = abs($expectedAtDepot - $returnedCount) + $damagedCount + $missingCount;
                    $severity = ($discrepancySum > 10) ? IncidentSeverity::HIGH : IncidentSeverity::MEDIUM;
                    $desc = "Selisih retur wadah di depot SPPG dari {$school->name}: Diterima {$returnedCount} dari {$expectedAtDepot} diambil. Rusak: {$damagedCount}, Hilang: {$missingCount}." . ($notes ? " Catatan: {$notes}" : '');

                    if ($existingDepotIncident) {
                        $existingDepotIncident->update([
                            'description' => $desc,
                            'severity' => $severity,
                        ]);
                    } else {
                        Incident::create([
                            'sppg_id' => $sppg->id,
                            'school_id' => $school->id,
                            'subject_type' => TrayReturn::class,
                            'subject_id' => $lockedReturn->id,
                            'reported_by' => $actor->id,
                            'category' => IncidentCategory::TRAY_DISCREPANCY,
                            'severity' => $severity,
                            'status' => IncidentStatus::OPEN,
                            'description' => $desc,
                        ]);
                    }
                } elseif ($existingDepotIncident) {
                    // Count was corrected to match expected. Do NOT delete or silently close open incident!
                    $existingDepotIncident->update([
                        'description' => $existingDepotIncident->description . " | Koreksi Depot: Jumlah diterima diperbarui menjadi {$returnedCount} (sesuai jumlah diambil). Menunggu peninjauan administratif Kepala SPPG.",
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
                        'returned_at' => $lockedReturn->returned_at->toIso8601String(),
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
