<?php

namespace App\Services;

use App\Enums\QualityCheckConclusion;
use App\Enums\QualityCheckStage;
use App\Enums\SchoolReceiptStatus;
use App\Enums\TripStatus;
use App\Enums\TripStopStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\EvidencePhoto;
use App\Models\QualityCheck;
use App\Models\SchoolServiceRecord;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SchoolHandoverService
{
    /**
     * Driver marks arrival at the active school stop (Page D03 / D04).
     * Enforces assigned driver authorization, active trip in-progress state,
     * and strictly sequential delivery stop order (Stop N cannot arrive before Stop N-1 is resolved).
     */
    public function markStopArrival(TripStop $stop, User $driver, ?UploadedFile $photo = null, ?string $notes = null): TripStop
    {
        $uploadedPaths = [];

        try {
            return DB::transaction(function () use ($stop, $driver, $photo, $notes, &$uploadedPaths) {
                $lockedStop = TripStop::where('id', $stop->id)
                    ->with(['trip', 'deliveryItems'])
                    ->lockForUpdate()
                    ->firstOrFail();


            $trip = Trip::where('id', $lockedStop->trip_id)
                ->lockForUpdate()
                ->firstOrFail();

            // 1. Authorization: Only the assigned driver can mark arrival
            if ((int) $trip->driver_id !== (int) $driver->id) {
                throw ValidationException::withMessages([
                    'driver' => ['Anda bukan kurir yang ditugaskan untuk perjalanan ini.'],
                ]);
            }

            // Tenant scope isolation
            if ((int) $trip->sppg_id !== (int) $driver->sppg_id) {
                throw ValidationException::withMessages([
                    'tenant' => ['Kurir berada di luar lingkup unit SPPG perjalanan ini.'],
                ]);
            }

            // 2. Precondition: Trip must be actively IN_PROGRESS
            if ($trip->status !== TripStatus::IN_PROGRESS) {
                throw ValidationException::withMessages([
                    'trip' => ["Penandaan kedatangan hanya dapat dilakukan saat perjalanan AKTIF. Status trip: {$trip->status->label()}."],
                ]);
            }

            // 3. Sequential Stop Enforcement:
            // Stop N cannot be marked ARRIVED while any preceding stop (sequence < N) is not resolved.
            $unresolvedPrecedingStops = TripStop::where('trip_id', $trip->id)
                ->where('sequence_number', '<', $lockedStop->sequence_number)
                ->whereNotIn('status', [
                    TripStopStatus::HANDED_OVER,
                    TripStopStatus::COMPLETED,
                    TripStopStatus::SKIPPED,
                ])
                ->exists();

            if ($unresolvedPrecedingStops) {
                throw ValidationException::withMessages([
                    'stop' => ['Pemberhentian sebelumnya belum selesai diserahterimakan. Urutan rute multi-stop harus dipatuhi secara berurutan.'],
                ]);
            }

            // 4. Idempotency: If already ARRIVED_AWAITING_CONFIRMATION or ARRIVED, do not fail
            if (in_array($lockedStop->status, [TripStopStatus::ARRIVED, TripStopStatus::ARRIVED_AWAITING_CONFIRMATION], true)) {
                // If a photo was supplied on retry, store it
                if ($photo) {
                    $this->storeStopEvidence($lockedStop, $driver, $photo, 'SCHOOL_HANDOVER', $notes);
                }
                return $lockedStop;
            }

            // Cannot arrive at a stop that has already completed or been skipped
            if (in_array($lockedStop->status, [TripStopStatus::HANDED_OVER, TripStopStatus::COMPLETED, TripStopStatus::SKIPPED], true)) {
                throw ValidationException::withMessages([
                    'stop' => ["Pemberhentian ini telah berstatus {$lockedStop->status->label()} dan tidak dapat diubah kembali ke status kedatangan."],
                ]);
            }

            $now = Carbon::now();
            $expectedPortions = (int) ($lockedStop->deliveryItems->isNotEmpty()
                ? $lockedStop->deliveryItems->sum('loaded_portions')
                : $lockedStop->loaded_portions);

            if ($expectedPortions <= 0) {
                throw ValidationException::withMessages([
                    'loaded_portions' => ['Muatan fisik stop ini bernilai 0 atau belum diverifikasi saat pemuatan. Tidak dapat menandai kedatangan tanpa manifest fisik sah.'],
                ]);
            }

            $lockedStop->update([
                'status' => TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
                'arrived_at' => $lockedStop->arrived_at ?: $now,
            ]);

            // Ensure SchoolServiceRecord exists in WAITING state for PIC
            SchoolServiceRecord::firstOrCreate(
                ['trip_stop_id' => $lockedStop->id],
                [
                    'expected_portions' => $expectedPortions,
                    'status' => SchoolReceiptStatus::WAITING,
                ]
            );

            if ($photo) {
                $this->storeStopEvidence($lockedStop, $driver, $photo, 'SCHOOL_HANDOVER', $notes, $uploadedPaths);
            }

            ActivityLog::record(
                event: 'trip_stop.arrived',
                auditable: $lockedStop,
                actor: $driver,
                newValues: [
                    'status' => TripStopStatus::ARRIVED_AWAITING_CONFIRMATION->value,
                    'arrived_at' => $now->toIso8601String(),
                ]
            );

            return $lockedStop;
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
     * PIC Sekolah verifies delivery manifest, performs arrival organoleptic check,
     * counts received/damaged/shortage portions, uploads evidence, and officially accepts delivery (Page P04).
     * Enforces strict separation of duties (Driver CANNOT self-approve),
     * school tenant isolation, and automatically activates the subsequent stop in the trip route.
     */
    public function confirmReceipt(TripStop $stop, User $pic, array $data, ?UploadedFile $evidencePhoto = null): SchoolServiceRecord
    {
        if (!$evidencePhoto && isset($data['evidence_photo']) && $data['evidence_photo'] instanceof UploadedFile) {
            $evidencePhoto = $data['evidence_photo'];
        }

        $uploadedPaths = [];

        try {
            return DB::transaction(function () use ($stop, $pic, $data, $evidencePhoto, &$uploadedPaths) {
                $lockedStop = TripStop::where('id', $stop->id)
                    ->with(['trip.stops', 'deliveryItems.foodBatch.menu', 'schoolServiceRecord'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $trip = Trip::where('id', $lockedStop->trip_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // 1. Role & Separation of Duties check
                if ($pic->role !== UserRole::PIC_SEKOLAH) {
                    throw ValidationException::withMessages([
                        'role' => ['Hanya petugas PIC Sekolah resmi yang berwenang memverifikasi dan menandatangani penerimaan makanan.'],
                    ]);
                }

                // Kurir cannot self-approve
                if ((int) $pic->id === (int) $trip->driver_id) {
                    throw ValidationException::withMessages([
                        'authorization' => ['Pelanggaran pemisahan tugas: Kurir pengantar tidak dapat mengonfirmasi penerimaan atas nama sekolah.'],
                    ]);
                }

                // Tenant isolation: PIC only accepts for their own assigned school
                if ((int) $pic->school_id !== (int) $lockedStop->school_id) {
                    throw ValidationException::withMessages([
                        'school' => ['Anda tidak memiliki otorisasi untuk menerima pengiriman sekolah lain.'],
                    ]);
                }

                // Idempotency: If already confirmed, return the existing record without modifying any values
                $existingRecord = SchoolServiceRecord::where('trip_stop_id', $lockedStop->id)->first();
                if ($existingRecord && $existingRecord->isConfirmed()) {
                    return $existingRecord;
                }

                // 2. Precondition: Stop must have ARRIVED
                if (!in_array($lockedStop->status, [TripStopStatus::ARRIVED, TripStopStatus::ARRIVED_AWAITING_CONFIRMATION], true)) {
                    throw new ConflictHttpException(
                        "Kurir belum menandai kedatangan di sekolah. Serah terima hanya dapat dilakukan setelah status tiba (ARRIVED). Status saat ini: {$lockedStop->status->label()}."
                    );
                }

                // 3. Portions calculation & validation (GATE B & C: Zero loaded portions fails safe, no planned fallback)
                $expectedPortions = (int) ($lockedStop->deliveryItems->isNotEmpty()
                    ? $lockedStop->deliveryItems->sum('loaded_portions')
                    : $lockedStop->loaded_portions);

                if ($expectedPortions <= 0) {
                    throw ValidationException::withMessages([
                        'loaded_portions' => ['Muatan fisik stop ini bernilai 0 atau belum diverifikasi saat pemuatan. Tidak dapat membuat tanda terima tanpa manifest fisik sah.'],
                    ]);
                }

                $receivedPortions = filter_var($data['received_portions'] ?? null, FILTER_VALIDATE_INT);
                if ($receivedPortions === false || $receivedPortions < 0) {
                    throw ValidationException::withMessages([
                        'received_portions' => ['Jumlah porsi diterima wajib diisi dengan bilangan bulat positif atau 0.'],
                    ]);
                }

                $damagedPortions = 0;
                if (isset($data['damaged_portions']) && $data['damaged_portions'] !== '') {
                    $val = filter_var($data['damaged_portions'], FILTER_VALIDATE_INT);
                    if ($val === false || $val < 0) {
                        throw ValidationException::withMessages([
                            'damaged_portions' => ['Jumlah porsi rusak tidak boleh bernilai negatif.'],
                        ]);
                    }
                    $damagedPortions = $val;
                }

                if ($damagedPortions > $receivedPortions) {
                    throw ValidationException::withMessages([
                        'damaged_portions' => ['Jumlah porsi rusak tidak boleh melebihi jumlah porsi fisik yang diterima.'],
                    ]);
                }

                // Disallow unauthorized over-receipt beyond 150% threshold
                if ($receivedPortions > ($expectedPortions * 1.5)) {
                    throw ValidationException::withMessages([
                        'received_portions' => ["Jumlah porsi yang diterima ({$receivedPortions}) melebihi ambang batas toleransi manifest resmi ({$expectedPortions} porsi)."],
                    ]);
                }

                $shortagePortions = max(0, $expectedPortions - $receivedPortions);
                $overagePortions = max(0, $receivedPortions - $expectedPortions);
                $notes = isset($data['notes']) ? trim((string) $data['notes']) : null;

                // GATE C: Discrepancy enforcement
                if ($shortagePortions > 0 || $damagedPortions > 0 || $overagePortions > 0) {
                    $receiptStatus = SchoolReceiptStatus::RECEIVED_WITH_DISCREPANCY;
                    if (empty($notes)) {
                        throw ValidationException::withMessages([
                            'notes' => ['Catatan penjelasan wajib diisi jika terdapat selisih jumlah porsi (kurang/lebih) atau porsi makanan rusak.'],
                        ]);
                    }
                } else {
                    $receiptStatus = SchoolReceiptStatus::RECEIVED;
                }

                // 4. Organoleptic Quality Check on Arrival
                $appearanceOk = filter_var($data['appearance_ok'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $aromaOk = filter_var($data['aroma_ok'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $textureOk = filter_var($data['texture_ok'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $tasteTested = filter_var($data['taste_tested'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $tasteOk = isset($data['taste_ok']) ? filter_var($data['taste_ok'], FILTER_VALIDATE_BOOLEAN) : null;
                $tasteSkippedReason = isset($data['taste_skipped_reason']) ? trim((string) $data['taste_skipped_reason']) : null;

                // Food Safety: Do NOT force tasting if food is suspected spoiled or damaged!
                if (!$appearanceOk || !$aromaOk) {
                    if ($tasteTested && $tasteOk === true) {
                        throw ValidationException::withMessages([
                            'taste_tested' => ['Makanan dengan rupa atau aroma tidak wajar dilarang dicicipi demi keamanan petugas/siswa.'],
                        ]);
                    }
                }

                $tempC = null;
                if (isset($data['temperature_c']) && $data['temperature_c'] !== '') {
                    $val = filter_var($data['temperature_c'], FILTER_VALIDATE_FLOAT);
                    if ($val !== false) {
                        $tempC = $val;
                    }
                }

                // Conclusion evaluation
                $rawConclusion = $data['conclusion'] ?? null;
                if ($rawConclusion instanceof QualityCheckConclusion) {
                    $conclusion = $rawConclusion;
                } elseif (is_string($rawConclusion) && in_array(strtoupper($rawConclusion), ['SAFE', 'UNSAFE', 'HOLD'], true)) {
                    $conclusion = QualityCheckConclusion::from(strtoupper($rawConclusion));
                } else {
                    // Derived conclusion from checks
                    if (!$appearanceOk || !$aromaOk || !$textureOk || ($tasteTested && $tasteOk === false)) {
                        $conclusion = QualityCheckConclusion::UNSAFE;
                    } else {
                        $conclusion = QualityCheckConclusion::SAFE;
                    }
                }

                // Invariant: Unsafe sensory checks CANNOT be marked SAFE
                if ((!$appearanceOk || !$aromaOk || !$textureOk || ($tasteTested && $tasteOk === false)) && $conclusion === QualityCheckConclusion::SAFE) {
                    throw ValidationException::withMessages([
                        'conclusion' => ['Hasil pemeriksaan organoleptik yang tidak normal tidak dapat dinyatakan Lolos QC (SAFE).'],
                    ]);
                }

                // GATE E: Batch Provenance — Only check batches on this stop's delivery items. NEVER fall back cross-stop!
                $distinctBatches = $lockedStop->deliveryItems->pluck('food_batch_id')->filter()->unique();
                if ($distinctBatches->isEmpty()) {
                    throw ValidationException::withMessages([
                        'batches' => ['Pemberhentian ini tidak memiliki alokasi batch makanan yang sah pada manifest pengiriman.'],
                    ]);
                }

                $totalDeliveryItems = $lockedStop->deliveryItems->count();
                $isMultiBatch = $totalDeliveryItems > 1;
                $now = Carbon::now();

                // Check if client provided verified per-item receipts
                $verificationMode = strtoupper((string) ($data['verification_mode'] ?? ''));
                $hasVerifiedItemInputs = ($verificationMode === 'PER_BATCH') || (!empty($data['items']) && is_array($data['items']));
                $verifiedItemMap = [];

                if ($hasVerifiedItemInputs) {
                    if (empty($data['items']) || !is_array($data['items'])) {
                        throw ValidationException::withMessages([
                            'items' => ['Rincian fisik per-batch wajib dikirimkan dalam mode verifikasi per batch.'],
                        ]);
                    }

                    $validItemIds = $lockedStop->deliveryItems->pluck('id')->all();
                    $submittedItemIds = array_map('intval', array_keys($data['items']));

                    // GATE 1: All items belonging to stop must be present
                    $missingItemIds = array_diff($validItemIds, $submittedItemIds);
                    if (!empty($missingItemIds)) {
                        throw ValidationException::withMessages([
                            'items' => ['Seluruh item batch pengiriman wajib diverifikasi secara lengkap. Item berikut belum memiliki data verifikasi: #' . implode(', #', $missingItemIds) . '.'],
                        ]);
                    }

                    $foreignItemIds = array_diff($submittedItemIds, $validItemIds);
                    if (!empty($foreignItemIds)) {
                        throw ValidationException::withMessages([
                            'items' => ['Terdapat item pengiriman yang tidak terdaftar pada pemberhentian sekolah ini.'],
                        ]);
                    }

                    $itemReceivedSum = 0;
                    $itemDamagedSum = 0;

                    foreach ($lockedStop->deliveryItems as $item) {
                        $itemRow = $data['items'][$item->id] ?? null;
                        if (!is_array($itemRow)) {
                            throw ValidationException::withMessages([
                                "items.{$item->id}" => ["Data item #{$item->id} harus berupa array."],
                            ]);
                        }

                        if (!isset($itemRow['received_portions']) || $itemRow['received_portions'] === null || $itemRow['received_portions'] === '') {
                            throw ValidationException::withMessages([
                                "items.{$item->id}.received_portions" => ["Kuantitas diterima fisik item #{$item->id} wajib diisi eksplisit."],
                            ]);
                        }

                        $iRec = filter_var($itemRow['received_portions'], FILTER_VALIDATE_INT);
                        if ($iRec === false || $iRec < 0) {
                            throw ValidationException::withMessages([
                                "items.{$item->id}.received_portions" => ['Jumlah diterima per batch harus berupa bilangan bulat >= 0.'],
                            ]);
                        }

                        if (!isset($itemRow['damaged_portions']) || $itemRow['damaged_portions'] === null || $itemRow['damaged_portions'] === '') {
                            throw ValidationException::withMessages([
                                "items.{$item->id}.damaged_portions" => ["Kuantitas rusak fisik item #{$item->id} wajib diisi eksplisit (0 jika tidak ada kerusakan)."],
                            ]);
                        }

                        $iDmg = filter_var($itemRow['damaged_portions'], FILTER_VALIDATE_INT);
                        if ($iDmg === false || $iDmg < 0 || $iDmg > $iRec) {
                            throw ValidationException::withMessages([
                                "items.{$item->id}.damaged_portions" => ['Jumlah rusak per batch tidak boleh melebihi jumlah diterima fisik pada batch tersebut.'],
                            ]);
                        }

                        $verifiedItemMap[$item->id] = [
                            'received' => $iRec,
                            'damaged' => $iDmg,
                        ];
                        $itemReceivedSum += $iRec;
                        $itemDamagedSum += $iDmg;
                    }

                    if ($itemReceivedSum !== $receivedPortions) {
                        throw ValidationException::withMessages([
                            'received_portions' => ["Total porsi fisik per-batch ({$itemReceivedSum}) harus sama dengan jumlah diterima ({$receivedPortions})."],
                        ]);
                    }
                    if ($itemDamagedSum !== $damagedPortions) {
                        throw ValidationException::withMessages([
                            'damaged_portions' => ["Total porsi rusak per-batch ({$itemDamagedSum}) harus sama dengan jumlah rusak ({$damagedPortions})."],
                        ]);
                    }
                }

                // 5. Decision & State Machine Transitions
                if ($conclusion === QualityCheckConclusion::UNSAFE || $conclusion === QualityCheckConclusion::HOLD) {
                    // QC FAILED -> HOLD delivery for food safety!
                    $receiptStatus = SchoolReceiptStatus::HELD;
                    $lockedStop->update([
                        'status' => TripStopStatus::EXCEPTION,
                    ]);
                } else {
                    $lockedStop->update([
                        'status' => TripStopStatus::HANDED_OVER,
                        'handed_over_at' => $now,
                    ]);

                    // 6. NEXT STOP ACTIVATION (GATE I)
                    // Only activate next pending stop if current stop was successfully handed over
                    $nextStop = TripStop::where('trip_id', $trip->id)
                        ->where('sequence_number', '>', $lockedStop->sequence_number)
                        ->where('status', TripStopStatus::PENDING)
                        ->orderBy('sequence_number')
                        ->first();

                    if ($nextStop) {
                        $nextStop->update([
                            'status' => TripStopStatus::EN_ROUTE,
                        ]);

                        ActivityLog::record(
                            event: 'trip_stop.activated_en_route',
                            auditable: $nextStop,
                            actor: $pic,
                            newValues: [
                                'status' => TripStopStatus::EN_ROUTE->value,
                                'activated_after_stop_id' => $lockedStop->id,
                            ]
                        );
                    }
                }

                // GATE A, B, C, D: RECONCILE DELIVERY ITEMS & RECORD QUALITY CHECKS
                if ($hasVerifiedItemInputs) {
                    // Skenario 1: Data verifikasi fisik per-item/batch tersedia
                    foreach ($lockedStop->deliveryItems as $item) {
                        $vData = $verifiedItemMap[$item->id];
                        $itemLoaded = (int) ($item->loaded_portions ?? 0);
                        $itemRec = $vData['received'];
                        $itemDmg = $vData['damaged'];

                        // GATE 3: Physical Bounds & Over-Receipt Defense
                        // Legitimate portions cannot exceed loaded_portions
                        $legitReceived = min($itemLoaded, $itemRec);
                        $legitDamaged = min($legitReceived, $itemDmg);
                        $legitGood = max(0, $legitReceived - $legitDamaged);

                        $itemDelivered = min($itemLoaded, $itemRec);
                        $item->update([
                            'delivered_portions' => $itemDelivered,
                            'status' => $itemDelivered > 0 ? 'DELIVERED' : ($itemRec === 0 ? 'NOT_DELIVERED' : 'DELIVERED'),
                        ]);

                        if ($conclusion === QualityCheckConclusion::SAFE) {
                            $batchAccepted = $legitGood;
                            $batchRejected = $legitDamaged;
                        } else {
                            $batchAccepted = 0;
                            $batchRejected = $legitReceived;
                        }

                        $reasonNote = 'Verifikasi fisik per batch terkonfirmasi pada saat kedatangan di sekolah.';
                        if ($itemRec > $itemLoaded) {
                            $surplus = $itemRec - $itemLoaded;
                            $reasonNote .= " Terdeteksi selisih surplus fisik per batch: +{$surplus} porsi melebihi muatan manifes ({$itemLoaded} porsi).";
                        } elseif ($itemRec < $itemLoaded) {
                            $shortage = $itemLoaded - $itemRec;
                            $reasonNote .= " Terdeteksi kekurangan fisik per batch: -{$shortage} porsi dari muatan manifes ({$itemLoaded} porsi).";
                        }

                        QualityCheck::create([
                            'food_batch_id' => $item->food_batch_id,
                            'trip_stop_id' => $lockedStop->id,
                            'check_stage' => QualityCheckStage::ON_ARRIVAL,
                            'checked_by' => $pic->id,
                            'appearance_ok' => $appearanceOk,
                            'aroma_ok' => $aromaOk,
                            'texture_ok' => $textureOk,
                            'taste_tested' => $tasteTested,
                            'taste_ok' => $tasteOk,
                            'taste_skipped_reason' => $tasteSkippedReason,
                            'temperature_c' => $tempC,
                            'accepted_portions' => $batchAccepted,
                            'rejected_portions' => $batchRejected,
                            'conclusion' => $conclusion,
                            'notes' => $notes,
                            'decision_reason' => $reasonNote,
                            'checked_at' => $now,
                        ]);
                    }
                } elseif (!$isMultiBatch) {
                    // Skenario 2: Single batch on stop -> provenance 100% exact & unambiguous
                    $item = $lockedStop->deliveryItems->first();
                    $itemLoaded = (int) ($item->loaded_portions ?? 0);

                    // Legitimate portions cannot exceed loaded_portions
                    $legitReceived = min($itemLoaded, $receivedPortions);
                    $legitDamaged = min($legitReceived, $damagedPortions);
                    $legitGood = max(0, $legitReceived - $legitDamaged);

                    $itemDelivered = min($itemLoaded, $receivedPortions);
                    $item->update([
                        'delivered_portions' => $itemDelivered,
                        'status' => $itemDelivered > 0 ? 'DELIVERED' : ($receivedPortions === 0 ? 'NOT_DELIVERED' : 'DELIVERED'),
                    ]);

                    if ($conclusion === QualityCheckConclusion::SAFE) {
                        $batchAccepted = $legitGood;
                        $batchRejected = $legitDamaged;
                    } else {
                        $batchAccepted = 0;
                        $batchRejected = $legitReceived;
                    }

                    $reasonNote = 'Pemeriksaan organoleptik kedatangan single batch terverifikasi.';
                    if ($receivedPortions > $itemLoaded) {
                        $surplus = $receivedPortions - $itemLoaded;
                        $reasonNote .= " Terdeteksi selisih surplus fisik stop: +{$surplus} porsi melebihi muatan manifes ({$itemLoaded} porsi).";
                    }

                    QualityCheck::create([
                        'food_batch_id' => $item->food_batch_id,
                        'trip_stop_id' => $lockedStop->id,
                        'check_stage' => QualityCheckStage::ON_ARRIVAL,
                        'checked_by' => $pic->id,
                        'appearance_ok' => $appearanceOk,
                        'aroma_ok' => $aromaOk,
                        'texture_ok' => $textureOk,
                        'taste_tested' => $tasteTested,
                        'taste_ok' => $tasteOk,
                        'taste_skipped_reason' => $tasteSkippedReason,
                        'temperature_c' => $tempC,
                        'accepted_portions' => $batchAccepted,
                        'rejected_portions' => $batchRejected,
                        'conclusion' => $conclusion,
                        'notes' => $notes,
                        'decision_reason' => $reasonNote,
                        'checked_at' => $now,
                    ]);
                } else {
                    // Skenario 3: Multi-batch AGGREGATE receipt (no per-item breakdown provided)
                    $hasDiscrepancy = ($receivedPortions !== $expectedPortions) || ($damagedPortions > 0);

                    if (!$hasDiscrepancy) {
                        // Exact clean receipt: every batch arrived in full without discrepancy!
                        foreach ($lockedStop->deliveryItems as $item) {
                            $itemLoaded = (int) ($item->loaded_portions ?? 0);
                            $item->update([
                                'delivered_portions' => $itemLoaded,
                                'status' => 'DELIVERED',
                            ]);

                            QualityCheck::create([
                                'food_batch_id' => $item->food_batch_id,
                                'trip_stop_id' => $lockedStop->id,
                                'check_stage' => QualityCheckStage::ON_ARRIVAL,
                                'checked_by' => $pic->id,
                                'appearance_ok' => $appearanceOk,
                                'aroma_ok' => $aromaOk,
                                'texture_ok' => $textureOk,
                                'taste_tested' => $tasteTested,
                                'taste_ok' => $tasteOk,
                                'taste_skipped_reason' => $tasteSkippedReason,
                                'temperature_c' => $tempC,
                                'accepted_portions' => $itemLoaded, // Exact loaded, no rounding drift!
                                'rejected_portions' => 0,
                                'conclusion' => $conclusion,
                                'notes' => $notes,
                                'decision_reason' => 'Penerimaan multi-batch lengkap sesuai manifest muatan tanpa selisih.',
                                'checked_at' => $now,
                            ]);
                        }
                    } else {
                        // Multi-batch WITH discrepancy (shortage, damage, or over-receipt) AND no per-batch breakdown
                        // GATE 2: DO NOT USE FIFO TO INVENT DELIVERED PORTIONS!
                        // When per-batch attribution is unresolved, delivered_portions is NULL!
                        foreach ($lockedStop->deliveryItems as $item) {
                            $item->update([
                                'delivered_portions' => null,
                                'status' => 'UNRESOLVED_DISCREPANCY',
                            ]);
                        }

                        // Record QualityCheck with unallocated portions (null accepted, null rejected) to prevent fabricated provenance
                        foreach ($distinctBatches as $batchId) {
                            QualityCheck::create([
                                'food_batch_id' => $batchId,
                                'trip_stop_id' => $lockedStop->id,
                                'check_stage' => QualityCheckStage::ON_ARRIVAL,
                                'checked_by' => $pic->id,
                                'appearance_ok' => $appearanceOk,
                                'aroma_ok' => $aromaOk,
                                'texture_ok' => $textureOk,
                                'taste_tested' => $tasteTested,
                                'taste_ok' => $tasteOk,
                                'taste_skipped_reason' => $tasteSkippedReason,
                                'temperature_c' => $tempC,
                                'accepted_portions' => null, // Unattributed at batch level
                                'rejected_portions' => null, // Unknown batch damage, NOT false 0!
                                'conclusion' => $conclusion,
                                'notes' => $notes,
                                'decision_reason' => "Pemeriksaan kedatangan organoleptik multi-batch ({$totalDeliveryItems} batch). Atribusi porsi diterima dan rusak belum dirinci per batch sehingga dicatat sebagai unresolved discrepancy pada tingkat stop sekolah.",
                                'checked_at' => $now,
                            ]);
                        }
                    }
                }

                // Persist SchoolServiceRecord
                $serviceRecord = SchoolServiceRecord::updateOrCreate(
                    ['trip_stop_id' => $lockedStop->id],
                    [
                        'received_by' => $pic->id,
                        'received_at' => $now,
                        'expected_portions' => $expectedPortions,
                        'received_portions' => $receivedPortions,
                        'damaged_portions' => $damagedPortions,
                        'shortage_portions' => $shortagePortions,
                        'status' => $receiptStatus,
                        'notes' => $notes,
                    ]
                );

                // Store evidence photo if provided (GATE E: with full rollback tracking)
                if ($evidencePhoto) {
                    $this->storeRecordEvidence($serviceRecord, $pic, $evidencePhoto, 'RECEIPT_CONFIRMATION', $notes, $uploadedPaths);
                }

                ActivityLog::record(
                    event: 'school_service.receipt_confirmed',
                    auditable: $serviceRecord,
                    actor: $pic,
                    newValues: [
                        'status' => $receiptStatus->value,
                        'expected_portions' => $expectedPortions,
                        'received_portions' => $receivedPortions,
                        'damaged_portions' => $damagedPortions,
                        'shortage_portions' => $shortagePortions,
                        'conclusion' => $conclusion->value,
                    ]
                );

                return $serviceRecord;
            });
        } catch (\Throwable $e) {
            // Clean up orphan file if database transaction rolled back
            foreach ($uploadedPaths as $path) {
                if ($path && Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                }
            }
            throw $e;
        }
    }

    /**
     * Store evidence photo attached to a TripStop.
     */
    protected function storeStopEvidence(TripStop $stop, User $uploader, UploadedFile $file, string $type, ?string $notes = null, array &$uploadedPaths = []): EvidencePhoto
    {
        $path = $file->store('evidence_photos/stops', 'local');
        $uploadedPaths[] = $path;

        return EvidencePhoto::create([
            'evidenceable_type' => TripStop::class,
            'evidenceable_id' => $stop->id,
            'evidence_type' => $type,
            'uploaded_by' => $uploader->id,
            'file_path' => $path,
            'mime_type' => $file->getClientMimeType() ?: 'image/jpeg',
            'file_size' => $file->getSize() ?: 0,
            'captured_at' => Carbon::now(),
            'notes' => $notes,
        ]);
    }

    /**
     * Store evidence photo attached to a SchoolServiceRecord.
     */
    protected function storeRecordEvidence(SchoolServiceRecord $record, User $uploader, UploadedFile $file, string $type, ?string $notes = null, array &$uploadedPaths = []): EvidencePhoto
    {
        $path = $file->store('evidence_photos/receipts', 'local');
        $uploadedPaths[] = $path;

        return EvidencePhoto::create([
            'evidenceable_type' => SchoolServiceRecord::class,
            'evidenceable_id' => $record->id,
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


