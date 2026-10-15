<?php

namespace App\Services;

use App\Enums\IncidentCategory;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Enums\QualityCheckConclusion;
use App\Enums\QualityCheckStage;
use App\Enums\SchoolReceiptStatus;
use App\Enums\TripStatus;
use App\Enums\TripStopStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\EvidencePhoto;
use App\Models\Incident;
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
                    // Skenario 1: Data verifikasi fisik per-item/batch tersedia (Mode A: Per-Batch Verified)
                    foreach ($lockedStop->deliveryItems as $item) {
                        $vData = $verifiedItemMap[$item->id];
                        $itemLoaded = (int) ($item->loaded_portions ?? 0);
                        $itemRec = $vData['received'];
                        $itemDmg = $vData['damaged'];

                        if ($itemLoaded === 0) {
                            $item->update([
                                'delivered_portions' => 0,
                                'status' => 'NOT_DELIVERED',
                            ]);
                            $batchAccepted = 0;
                            $batchRejected = 0;
                            $reasonNote = 'Item tanpa muatan fisik (0 porsi) pada manifes muatan.';
                        } elseif ($itemRec > $itemLoaded) {
                            // GATE 2: Physical Bounds & Mixed Surplus-Damage Provenance
                            $surplus = $itemRec - $itemLoaded;

                            if ($itemDmg > 0 && $itemDmg < $itemRec) {
                                // Kerusakan tercampur dengan porsi surplus tanpa pemisahan kontainer fisik.
                                // Tidak boleh menggunakan min() untuk memaksakan kerusakan ke muatan manifes resmi!
                                $item->update([
                                    'delivered_portions' => null,
                                    'status' => 'UNRESOLVED_DISCREPANCY',
                                ]);
                                $batchAccepted = null;
                                $batchRejected = null;
                                $reasonNote = "Rekonsiliasi fisik anomali muatan: Jumlah loaded resmi: {$itemLoaded} porsi. Jumlah fisik observed: {$itemRec} porsi. Jumlah damaged observed: {$itemDmg} porsi. Jumlah surplus: {$surplus} porsi. Jumlah yang benar-benar dapat diatribusikan: 0 porsi. Jumlah yang belum bisa diatribusikan: {$itemLoaded} porsi. Kerusakan fisik tercampur dengan porsi surplus tanpa pemisahan kontainer fisik sehingga asal porsi rusak berstatus unknown demi integritas provenance pangan.";
                            } elseif ($itemDmg === 0) {
                                // Seluruh fisik diamati baik -> seluruh muatan manifes teratribusi baik
                                $itemDelivered = $itemLoaded;
                                $batchAccepted = ($conclusion === QualityCheckConclusion::SAFE) ? $itemLoaded : 0;
                                $batchRejected = 0;
                                $item->update([
                                    'delivered_portions' => $itemDelivered,
                                    'status' => 'DELIVERED',
                                ]);
                                $reasonNote = "Rekonsiliasi fisik surplus muatan: Jumlah loaded resmi: {$itemLoaded} porsi. Jumlah fisik observed: {$itemRec} porsi. Jumlah damaged observed: 0 porsi. Jumlah surplus: {$surplus} porsi. Jumlah yang benar-benar dapat diatribusikan: {$itemLoaded} porsi. Jumlah yang belum bisa diatribusikan: 0 porsi. Seluruh porsi fisik diamati dalam kondisi baik sehingga muatan manifes teratribusi aman.";
                            } else {
                                // 100% rusak -> seluruh muatan manifes teratribusi rusak
                                $itemDelivered = $itemLoaded;
                                $batchAccepted = 0;
                                $batchRejected = $itemLoaded;
                                $item->update([
                                    'delivered_portions' => $itemDelivered,
                                    'status' => 'DELIVERED',
                                ]);
                                $reasonNote = "Rekonsiliasi fisik surplus muatan rusak: Jumlah loaded resmi: {$itemLoaded} porsi. Jumlah fisik observed: {$itemRec} porsi. Jumlah damaged observed: {$itemDmg} porsi. Jumlah surplus: {$surplus} porsi. Jumlah yang benar-benar dapat diatribusikan: {$itemLoaded} porsi. Jumlah yang belum bisa diatribusikan: 0 porsi. Seluruh porsi fisik diamati dalam kondisi rusak sehingga muatan manifes teratribusi rusak.";
                            }
                        } else {
                            // Tidak ada surplus ($itemRec <= $itemLoaded) -> seluruh fisik bagian dari muatan manifes
                            $legitReceived = $itemRec;
                            $legitDamaged = min($legitReceived, $itemDmg);
                            $legitGood = max(0, $legitReceived - $legitDamaged);

                            $itemDelivered = $itemRec;
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
                            if ($itemRec < $itemLoaded) {
                                $shortage = $itemLoaded - $itemRec;
                                $reasonNote .= " Terdeteksi kekurangan fisik per batch: -{$shortage} porsi dari muatan manifes ({$itemLoaded} porsi).";
                            }
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
                    // Skenario 2: Single batch on stop -> provenance 100% exact & unambiguous (single source batch)
                    $item = $lockedStop->deliveryItems->first();
                    $itemLoaded = (int) ($item->loaded_portions ?? 0);
                    $itemRec = $receivedPortions;
                    $itemDmg = $damagedPortions;

                    if ($itemLoaded === 0) {
                        $item->update([
                            'delivered_portions' => 0,
                            'status' => 'NOT_DELIVERED',
                        ]);
                        $batchAccepted = 0;
                        $batchRejected = 0;
                        $reasonNote = 'Item tanpa muatan fisik (0 porsi) pada manifes muatan.';
                    } elseif ($itemRec > $itemLoaded) {
                        // GATE 2: Physical Bounds & Mixed Surplus-Damage Provenance on Single Batch
                        $surplus = $itemRec - $itemLoaded;

                        if ($itemDmg > 0 && $itemDmg < $itemRec) {
                            $item->update([
                                'delivered_portions' => null,
                                'status' => 'UNRESOLVED_DISCREPANCY',
                            ]);
                            $batchAccepted = null;
                            $batchRejected = null;
                            $reasonNote = "Rekonsiliasi fisik anomali muatan: Jumlah loaded resmi: {$itemLoaded} porsi. Jumlah fisik observed: {$itemRec} porsi. Jumlah damaged observed: {$itemDmg} porsi. Jumlah surplus: {$surplus} porsi. Jumlah yang benar-benar dapat diatribusikan: 0 porsi. Jumlah yang belum bisa diatribusikan: {$itemLoaded} porsi. Kerusakan fisik tercampur dengan porsi surplus tanpa pemisahan kontainer fisik sehingga asal porsi rusak berstatus unknown demi integritas provenance pangan.";
                        } elseif ($itemDmg === 0) {
                            $itemDelivered = $itemLoaded;
                            $batchAccepted = ($conclusion === QualityCheckConclusion::SAFE) ? $itemLoaded : 0;
                            $batchRejected = 0;
                            $item->update([
                                'delivered_portions' => $itemDelivered,
                                'status' => 'DELIVERED',
                            ]);
                            $reasonNote = "Rekonsiliasi fisik surplus muatan: Jumlah loaded resmi: {$itemLoaded} porsi. Jumlah fisik observed: {$itemRec} porsi. Jumlah damaged observed: 0 porsi. Jumlah surplus: {$surplus} porsi. Jumlah yang benar-benar dapat diatribusikan: {$itemLoaded} porsi. Jumlah yang belum bisa diatribusikan: 0 porsi. Seluruh porsi fisik diamati dalam kondisi baik sehingga muatan manifes teratribusi aman.";
                        } else {
                            $itemDelivered = $itemLoaded;
                            $batchAccepted = 0;
                            $batchRejected = $itemLoaded;
                            $item->update([
                                'delivered_portions' => $itemDelivered,
                                'status' => 'DELIVERED',
                            ]);
                            $reasonNote = "Rekonsiliasi fisik surplus muatan rusak: Jumlah loaded resmi: {$itemLoaded} porsi. Jumlah fisik observed: {$itemRec} porsi. Jumlah damaged observed: {$itemDmg} porsi. Jumlah surplus: {$surplus} porsi. Jumlah yang benar-benar dapat diatribusikan: {$itemLoaded} porsi. Jumlah yang belum bisa diatribusikan: 0 porsi. Seluruh porsi fisik diamati dalam kondisi rusak sehingga muatan manifes teratribusi rusak.";
                        }
                    } else {
                        $legitReceived = $itemRec;
                        $legitDamaged = min($legitReceived, $itemDmg);
                        $legitGood = max(0, $legitReceived - $legitDamaged);

                        $itemDelivered = $itemRec;
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

                        $reasonNote = 'Pemeriksaan organoleptik kedatangan single batch terverifikasi.';
                        if ($itemRec < $itemLoaded) {
                            $shortage = $itemLoaded - $itemRec;
                            $reasonNote .= " Terdeteksi kekurangan fisik stop: -{$shortage} porsi dari muatan manifes ({$itemLoaded} porsi).";
                        }
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
                    // Skenario 3: Multi-batch AGGREGATE receipt (Mode B: no per-item breakdown provided)
                    // GATE 1: Mode AGGREGATE dengan total yang cocok tidak membuktikan setiap batch cocok!
                    // Contoh: Batch A loaded 100, Batch B loaded 100. Total diterima 200, damaged 0.
                    // Kemungkinan fisik di lapangan: Batch A 80, Batch B 120 (total tetap 200).
                    // Karena PIC tidak melakukan verifikasi fisik per batch/kontainer/segel (Mode B),
                    // sistem TIDAK BOLEH mengklaim jumlah delivered dan hasil QC individual per batch sebagai fakta!
                    //
                    // Pemisahan integritas data:
                    // 1. Stop total receipt verified: tercatat sah pada SchoolServiceRecord ($receivedPortions).
                    // 2. Status operasional stop tetap sah (HANDED_OVER jika SAFE, mengaktifkan stop berikutnya).
                    // 3. Per-batch receipt verified: TIDAK ADA (tidak ada bukti fisik per batch).
                    // 4. Per-batch attribution unknown: delivery_items.delivered_portions = null, status = 'UNRESOLVED_DISCREPANCY',
                    //    QualityCheck.accepted_portions = null, QualityCheck.rejected_portions = null.

                    $hasDiscrepancy = ($receivedPortions !== $expectedPortions) || ($damagedPortions > 0);

                    foreach ($lockedStop->deliveryItems as $item) {
                        $item->update([
                            'delivered_portions' => null,
                            'status' => 'UNRESOLVED_DISCREPANCY',
                        ]);
                    }

                    foreach ($distinctBatches as $batchId) {
                        $reason = $hasDiscrepancy
                            ? "Pemeriksaan kedatangan organoleptik multi-batch ({$totalDeliveryItems} batch). Total fisik stop selisih ({$receivedPortions} vs {$expectedPortions}) dan pemeriksaan fisik per batch tidak dilakukan (Mode B: Aggregate), sehingga atribusi porsi per batch berstatus unknown / unresolved discrepancy."
                            : "Pemeriksaan kedatangan organoleptik multi-batch ({$totalDeliveryItems} batch). Penerimaan total fisik tingkat stop terverifikasi lengkap ({$receivedPortions} porsi), namun pemeriksaan fisik per batch tidak dilakukan (Mode B: Aggregate), sehingga atribusi porsi delivered dan QC per batch berstatus unknown / tidak terverifikasi demi menjaga kejujuran provenance pangan.";

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
                            'rejected_portions' => null, // Unknown batch damage
                            'conclusion' => $conclusion,
                            'notes' => $notes,
                            'decision_reason' => $reason,
                            'checked_at' => $now,
                        ]);
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

                // Initialize TrayReturn in IN_USE state for reverse logistics traceability (PBI-12 Gate 2)
                // Container count is physically distinct from food portions. Kept null/unverified until physical manifest exists.
                if (! $serviceRecord->trayReturn()->exists()) {
                    app(\App\Services\TrayReturnService::class)->initiateForRecord($serviceRecord, null, false);
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

    /**
     * PIC Sekolah performs pre-consumption inspection (BEFORE_CONSUMPTION / second QC checkpoint).
     * Enforces that receipt was legally completed, sensory checks are independently verified,
     * food suspected unsafe is not tasted, and transitions status to CLEARED_TO_SERVE (if SAFE)
     * or HELD (if UNSAFE/HOLD) with an automatic food safety incident.
     */
    public function recordPreConsumptionCheck(SchoolServiceRecord $record, User $pic, array $data, ?UploadedFile $photo = null): SchoolServiceRecord
    {
        $uploadedPaths = [];

        try {
            return DB::transaction(function () use ($record, $pic, $data, $photo, &$uploadedPaths) {
                $lockedRecord = SchoolServiceRecord::where('id', $record->id)
                    ->with(['tripStop.trip', 'tripStop.school', 'tripStop.deliveryItems.foodBatch'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $stop = $lockedRecord->tripStop;
                $school = $stop->school;

                // 1. Role & Tenant Authorization
                if ($pic->role !== UserRole::PIC_SEKOLAH) {
                    throw ValidationException::withMessages([
                        'role' => ['Hanya petugas PIC Sekolah resmi yang berwenang melakukan pemeriksaan sebelum konsumsi.'],
                    ]);
                }

                if ((int) $pic->school_id !== (int) $stop->school_id) {
                    throw new HttpException(403, 'Akses ditolak. Anda tidak memiliki otorisasi untuk sekolah ini.');
                }

                // 2. Precondition: Receipt must be officially completed (RECEIVED or RECEIVED_WITH_DISCREPANCY)
                if ($lockedRecord->status === SchoolReceiptStatus::WAITING) {
                    throw new ConflictHttpException(
                        'Penerimaan makanan belum selesai (WAITING). Pemeriksaan sebelum makan hanya dapat dilakukan setelah serah terima makanan sah.'
                    );
                }

                if (in_array($lockedRecord->status, [SchoolReceiptStatus::HELD, SchoolReceiptStatus::REJECTED], true)) {
                    throw new ConflictHttpException(
                        "Layanan makanan berstatus {$lockedRecord->status->label()} karena kendala keamanan pangan. Pemeriksaan tidak dapat dilanjutkan."
                    );
                }

                if (in_array($lockedRecord->status, [SchoolReceiptStatus::DISTRIBUTED, SchoolReceiptStatus::MEAL_COMPLETED], true)) {
                    throw new ConflictHttpException(
                        "Pemeriksaan sebelum konsumsi tidak dapat diulang karena kegiatan pembagian makanan telah berlangsung atau selesai ({$lockedRecord->status->label()})."
                    );
                }

                // If already CLEARED_TO_SERVE, check idempotency
                if ($lockedRecord->status === SchoolReceiptStatus::CLEARED_TO_SERVE) {
                    return $lockedRecord;
                }

                // 3. Sensory checks
                $appearanceOk = filter_var($data['appearance_ok'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $aromaOk = filter_var($data['aroma_ok'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $textureOk = filter_var($data['texture_ok'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $tasteTested = filter_var($data['taste_tested'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $tasteOk = isset($data['taste_ok']) ? filter_var($data['taste_ok'], FILTER_VALIDATE_BOOLEAN) : null;
                $tasteSkippedReason = isset($data['taste_skipped_reason']) ? trim((string) $data['taste_skipped_reason']) : null;

                // Food safety rule: Do NOT force tasting if appearance or aroma is not OK!
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

                $rawConclusion = $data['conclusion'] ?? null;
                if ($rawConclusion instanceof QualityCheckConclusion) {
                    $conclusion = $rawConclusion;
                } elseif (is_string($rawConclusion) && in_array(strtoupper($rawConclusion), ['SAFE', 'UNSAFE', 'HOLD'], true)) {
                    $conclusion = QualityCheckConclusion::from(strtoupper($rawConclusion));
                } else {
                    if (!$appearanceOk || !$aromaOk || !$textureOk || ($tasteTested && $tasteOk === false)) {
                        $conclusion = QualityCheckConclusion::UNSAFE;
                    } else {
                        $conclusion = QualityCheckConclusion::SAFE;
                    }
                }

                if ((!$appearanceOk || !$aromaOk || !$textureOk || ($tasteTested && $tasteOk === false)) && $conclusion === QualityCheckConclusion::SAFE) {
                    throw ValidationException::withMessages([
                        'conclusion' => ['Hasil pemeriksaan organoleptik yang tidak normal tidak dapat dinyatakan Lolos Uji (SAFE).'],
                    ]);
                }

                $now = Carbon::now();
                $notes = isset($data['notes']) ? trim((string) $data['notes']) : null;

                // Record distinct QualityCheck with BEFORE_CONSUMPTION stage for each delivery item/batch
                $distinctBatches = $stop->deliveryItems->pluck('food_batch_id')->filter()->unique();
                foreach ($distinctBatches as $batchId) {
                    QualityCheck::create([
                        'food_batch_id' => $batchId,
                        'trip_stop_id' => $stop->id,
                        'check_stage' => QualityCheckStage::BEFORE_CONSUMPTION,
                        'checked_by' => $pic->id,
                        'appearance_ok' => $appearanceOk,
                        'aroma_ok' => $aromaOk,
                        'texture_ok' => $textureOk,
                        'taste_tested' => $tasteTested,
                        'taste_ok' => $tasteOk,
                        'taste_skipped_reason' => $tasteSkippedReason,
                        'temperature_c' => $tempC,
                        'accepted_portions' => $conclusion === QualityCheckConclusion::SAFE ? max(0, (int) $lockedRecord->received_portions - (int) $lockedRecord->damaged_portions) : 0,
                        'rejected_portions' => $conclusion === QualityCheckConclusion::SAFE ? 0 : (int) $lockedRecord->received_portions,
                        'conclusion' => $conclusion,
                        'notes' => $notes,
                        'decision_reason' => "Pemeriksaan organoleptik kedua sebelum konsumsi (BEFORE_CONSUMPTION) di {$school->name}.",
                        'checked_at' => $now,
                    ]);
                }

                if ($photo) {
                    $this->storeRecordEvidence($lockedRecord, $pic, $photo, 'PRE_CONSUMPTION_CHECK', $notes, $uploadedPaths);
                }

                // 4. Decision and state transition
                if ($conclusion === QualityCheckConclusion::SAFE) {
                    $lockedRecord->update([
                        'status' => SchoolReceiptStatus::CLEARED_TO_SERVE,
                    ]);

                    ActivityLog::record(
                        event: 'school_service.cleared_to_serve',
                        auditable: $lockedRecord,
                        actor: $pic,
                        newValues: [
                            'status' => SchoolReceiptStatus::CLEARED_TO_SERVE->value,
                            'conclusion' => $conclusion->value,
                        ]
                    );
                } else {
                    $lockedRecord->update([
                        'status' => SchoolReceiptStatus::HELD,
                    ]);

                    // Automatically file Incident for food safety escalation
                    Incident::create([
                        'sppg_id' => $stop->trip->sppg_id,
                        'school_id' => $school->id,
                        'subject_type' => SchoolServiceRecord::class,
                        'subject_id' => $lockedRecord->id,
                        'reported_by' => $pic->id,
                        'category' => IncidentCategory::FOOD_SAFETY,
                        'severity' => IncidentSeverity::CRITICAL,
                        'status' => IncidentStatus::OPEN,
                        'description' => "Pemeriksaan kedua sebelum konsumsi (BEFORE_CONSUMPTION) di {$school->name} menghasilkan kesimpulan {$conclusion->value}. Makanan ditahan dari pembagian siswa. Catatan: " . ($notes ?: 'Kondisi organoleptik tidak memenuhi standar kelayakan konsumsi.'),
                    ]);

                    ActivityLog::record(
                        event: 'school_service.held_unsafe',
                        auditable: $lockedRecord,
                        actor: $pic,
                        newValues: [
                            'status' => SchoolReceiptStatus::HELD->value,
                            'conclusion' => $conclusion->value,
                        ]
                    );
                }

                return $lockedRecord;
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
     * PIC marks start of meal distribution to students (Page P05).
     * Server timestamps distribution_started_at and transitions status to DISTRIBUTED.
     */
    public function startDistribution(SchoolServiceRecord $record, User $pic): SchoolServiceRecord
    {
        return DB::transaction(function () use ($record, $pic) {
            $lockedRecord = SchoolServiceRecord::where('id', $record->id)
                ->with(['tripStop.school'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($pic->role !== UserRole::PIC_SEKOLAH) {
                throw ValidationException::withMessages([
                    'role' => ['Hanya petugas PIC Sekolah resmi yang berwenang mencatat mulai pembagian makanan.'],
                ]);
            }

            if ((int) $pic->school_id !== (int) $lockedRecord->tripStop->school_id) {
                throw new HttpException(403, 'Akses ditolak. Anda tidak memiliki otorisasi untuk sekolah ini.');
            }

            // Precondition
            if ($lockedRecord->status === SchoolReceiptStatus::DISTRIBUTED) {
                return $lockedRecord; // Idempotent
            }

            if ($lockedRecord->status === SchoolReceiptStatus::MEAL_COMPLETED) {
                throw new ConflictHttpException('Kegiatan makan di sekolah ini telah selesai (MEAL_COMPLETED).');
            }

            if (in_array($lockedRecord->status, [SchoolReceiptStatus::HELD, SchoolReceiptStatus::REJECTED], true)) {
                throw new ConflictHttpException(
                    "Layanan makanan berstatus {$lockedRecord->status->label()}. Makanan dilarang dibagikan kepada siswa."
                );
            }

            if ($lockedRecord->status !== SchoolReceiptStatus::CLEARED_TO_SERVE) {
                throw new ConflictHttpException(
                    "Makanan belum dinyatakan lolos uji sebelum makan (CLEARED_TO_SERVE). Status saat ini: {$lockedRecord->status->label()}."
                );
            }

            $now = Carbon::now();
            $lockedRecord->update([
                'status' => SchoolReceiptStatus::DISTRIBUTED,
                'distribution_started_at' => $lockedRecord->distribution_started_at ?: $now,
            ]);

            ActivityLog::record(
                event: 'school_service.distribution_started',
                auditable: $lockedRecord,
                actor: $pic,
                newValues: [
                    'status' => SchoolReceiptStatus::DISTRIBUTED->value,
                    'distribution_started_at' => $now->toIso8601String(),
                ]
            );

            return $lockedRecord;
        });
    }

    /**
     * PIC records actual served portions, leftovers, and officially completes the meal service (Page P05).
     * Enforces:
     * - Only authorized school PIC.
     * - MEAL_COMPLETED cannot bypass BEFORE_CONSUMPTION check.
     * - Usable portions upper bound: served + leftover <= usable (received - damaged).
     * - Server timestamped meal_completed_at.
     * - Status transitions to MEAL_COMPLETED, unlocking P06 tray return readiness.
     */
    public function completeMeal(SchoolServiceRecord $record, User $pic, array $data, ?UploadedFile $photo = null): SchoolServiceRecord
    {
        $uploadedPaths = [];

        try {
            return DB::transaction(function () use ($record, $pic, $data, $photo, &$uploadedPaths) {
                $lockedRecord = SchoolServiceRecord::where('id', $record->id)
                    ->with(['tripStop.school', 'tripStop.qualityChecks'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($pic->role !== UserRole::PIC_SEKOLAH) {
                    throw ValidationException::withMessages([
                        'role' => ['Hanya petugas PIC Sekolah resmi yang berwenang menutup kegiatan makan.'],
                    ]);
                }

                if ((int) $pic->school_id !== (int) $lockedRecord->tripStop->school_id) {
                    throw new HttpException(403, 'Akses ditolak. Anda tidak memiliki otorisasi untuk sekolah ini.');
                }

                // Invariant: Cannot complete meal if in WAITING, HELD, or REJECTED
                if ($lockedRecord->status === SchoolReceiptStatus::WAITING) {
                    throw new ConflictHttpException('Penerimaan makanan belum selesai.');
                }

                if (in_array($lockedRecord->status, [SchoolReceiptStatus::HELD, SchoolReceiptStatus::REJECTED], true)) {
                    throw new ConflictHttpException(
                        "Layanan makanan berstatus {$lockedRecord->status->label()}. Kegiatan makan tidak dapat diselesaikan secara normal."
                    );
                }

                // Invariant: MEAL_COMPLETED cannot be achieved by skipping BEFORE_CONSUMPTION check
                $hasPassedPreConsumption = $lockedRecord->tripStop->qualityChecks()
                    ->whereIn('check_stage', [QualityCheckStage::BEFORE_CONSUMPTION, QualityCheckStage::PRE_CONSUMPTION])
                    ->where('conclusion', QualityCheckConclusion::SAFE)
                    ->exists();

                if (!$hasPassedPreConsumption) {
                    throw new ConflictHttpException(
                        'Pemeriksaan kualitas sebelum makan (BEFORE_CONSUMPTION) belum dilaksanakan atau belum lolos uji aman. Kegiatan makan tidak dapat diselesaikan tanpa verifikasi keselamatan pangan.'
                    );
                }

                // Idempotency: If already MEAL_COMPLETED
                if ($lockedRecord->status === SchoolReceiptStatus::MEAL_COMPLETED) {
                    return $lockedRecord;
                }

                // Usable portions calculation
                $usablePortions = max(0, (int) $lockedRecord->received_portions - (int) $lockedRecord->damaged_portions);

                $servedPortions = filter_var($data['served_portions'] ?? null, FILTER_VALIDATE_INT);
                if ($servedPortions === false || $servedPortions < 0) {
                    throw ValidationException::withMessages([
                        'served_portions' => ['Jumlah porsi dibagikan wajib diisi dengan bilangan bulat >= 0.'],
                    ]);
                }

                $leftoverPortions = 0;
                if (isset($data['leftover_portions']) && $data['leftover_portions'] !== '') {
                    $val = filter_var($data['leftover_portions'], FILTER_VALIDATE_INT);
                    if ($val === false || $val < 0) {
                        throw ValidationException::withMessages([
                            'leftover_portions' => ['Jumlah porsi sisa tidak boleh bernilai negatif.'],
                        ]);
                    }
                    $leftoverPortions = $val;
                }

                // Invariant: served + leftover must not exceed usable portions
                if (($servedPortions + $leftoverPortions) > $usablePortions) {
                    throw ValidationException::withMessages([
                        'served_portions' => [
                            "Total porsi terbagi ({$servedPortions}) dan sisa ({$leftoverPortions}) tidak boleh melampaui kuantitas porsi layak konsumsi ({$usablePortions} porsi)."
                        ],
                    ]);
                }

                $now = Carbon::now();
                $notes = isset($data['notes']) ? trim((string) $data['notes']) : null;

                $lockedRecord->update([
                    'served_portions' => $servedPortions,
                    'leftover_portions' => $leftoverPortions,
                    'distribution_started_at' => $lockedRecord->distribution_started_at ?: $now,
                    'meal_completed_at' => $now,
                    'status' => SchoolReceiptStatus::MEAL_COMPLETED,
                    'notes' => $notes ? ($lockedRecord->notes ? $lockedRecord->notes . ' | Selesai makan: ' . $notes : $notes) : $lockedRecord->notes,
                ]);

                if ($photo) {
                    $this->storeRecordEvidence($lockedRecord, $pic, $photo, 'MEAL_COMPLETION', $notes, $uploadedPaths);
                }

                ActivityLog::record(
                    event: 'school_service.meal_completed',
                    auditable: $lockedRecord,
                    actor: $pic,
                    newValues: [
                        'status' => SchoolReceiptStatus::MEAL_COMPLETED->value,
                        'served_portions' => $servedPortions,
                        'leftover_portions' => $leftoverPortions,
                        'meal_completed_at' => $now->toIso8601String(),
                    ]
                );

                return $lockedRecord;
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
}


