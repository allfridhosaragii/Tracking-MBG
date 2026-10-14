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
        return DB::transaction(function () use ($stop, $driver, $photo, $notes) {
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
            $expectedPortions = $lockedStop->total_loaded_portions ?: (int) $lockedStop->planned_portions;

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
                $this->storeStopEvidence($lockedStop, $driver, $photo, 'SCHOOL_HANDOVER', $notes);
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
    }

    /**
     * PIC Sekolah verifies delivery manifest, performs arrival organoleptic check,
     * counts received/damaged/shortage portions, uploads evidence, and officially accepts delivery (Page P04).
     * Enforces strict separation of duties (Driver CANNOT self-approve),
     * school tenant isolation, and automatically activates the subsequent stop in the trip route.
     */
    public function confirmReceipt(TripStop $stop, User $pic, array $data, ?UploadedFile $evidencePhoto = null): SchoolServiceRecord
    {
        return DB::transaction(function () use ($stop, $pic, $data, $evidencePhoto) {
            $lockedStop = TripStop::where('id', $stop->id)
                ->with(['trip.stops', 'deliveryItems', 'schoolServiceRecord'])
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

            // 2. Precondition: Stop must have ARRIVED
            if (!in_array($lockedStop->status, [TripStopStatus::ARRIVED, TripStopStatus::ARRIVED_AWAITING_CONFIRMATION], true)) {
                if (in_array($lockedStop->status, [TripStopStatus::HANDED_OVER, TripStopStatus::COMPLETED], true)) {
                    // Idempotent return if already handed over
                    $existing = SchoolServiceRecord::where('trip_stop_id', $lockedStop->id)->first();
                    if ($existing && $existing->status !== SchoolReceiptStatus::WAITING) {
                        return $existing;
                    }
                }

                throw new ConflictHttpException(
                    "Kurir belum menandai kedatangan di sekolah. Serah terima hanya dapat dilakukan setelah status tiba (ARRIVED). Status saat ini: {$lockedStop->status->label()}."
                );
            }

            // 3. Portions calculation & validation
            $expectedPortions = $lockedStop->deliveryItems->sum('loaded_portions') ?: (int) $lockedStop->planned_portions;
            if ($expectedPortions <= 0) {
                $expectedPortions = (int) $lockedStop->planned_portions;
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

            // Disallow unauthorized over-receipt without official manifest escalation
            if ($receivedPortions > ($expectedPortions * 1.5)) {
                throw ValidationException::withMessages([
                    'received_portions' => ["Jumlah porsi yang diterima ({$receivedPortions}) melebihi ambang manifest resmi ({$expectedPortions} porsi)."],
                ]);
            }

            $shortagePortions = max(0, $expectedPortions - $receivedPortions);

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
            if ((!$appearanceOk || !$aromaOk || !$textureOk) && $conclusion === QualityCheckConclusion::SAFE) {
                throw ValidationException::withMessages([
                    'conclusion' => ['Hasil pemeriksaan organoleptik yang tidak normal tidak dapat dinyatakan Lolos QC (SAFE).'],
                ]);
            }

            $primaryBatchId = $lockedStop->deliveryItems->first()?->food_batch_id;
            if (!$primaryBatchId) {
                // Fallback batch from trip if not linked on items
                $firstItem = \App\Models\DeliveryItem::whereHas('tripStop', function ($q) use ($trip) {
                    $q->where('trip_id', $trip->id);
                })->first();
                $primaryBatchId = $firstItem?->food_batch_id;
            }

            // Save Arrival Quality Check
            if ($primaryBatchId) {
                QualityCheck::create([
                    'food_batch_id' => $primaryBatchId,
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
                    'accepted_portions' => $conclusion === QualityCheckConclusion::SAFE ? $receivedPortions : 0,
                    'rejected_portions' => $conclusion === QualityCheckConclusion::SAFE ? $damagedPortions : $receivedPortions,
                    'conclusion' => $conclusion,
                    'notes' => $data['notes'] ?? null,
                    'checked_at' => Carbon::now(),
                ]);
            }

            $now = Carbon::now();
            $notes = $data['notes'] ?? null;

            // 5. Decision & State Machine Transitions
            if ($conclusion === QualityCheckConclusion::UNSAFE || $conclusion === QualityCheckConclusion::HOLD) {
                // QC FAILED -> HOLD delivery for food safety!
                $receiptStatus = SchoolReceiptStatus::HELD;
                $lockedStop->update([
                    'status' => TripStopStatus::EXCEPTION,
                ]);
            } else {
                // QC PASSED
                if ($shortagePortions > 0 || $damagedPortions > 0) {
                    $receiptStatus = SchoolReceiptStatus::RECEIVED_WITH_DISCREPANCY;
                } else {
                    $receiptStatus = SchoolReceiptStatus::RECEIVED;
                }

                $lockedStop->update([
                    'status' => TripStopStatus::HANDED_OVER,
                    'handed_over_at' => $now,
                ]);

                // Update delivered_portions on items
                foreach ($lockedStop->deliveryItems as $item) {
                    $item->update([
                        'delivered_portions' => $receivedPortions,
                    ]);
                }

                // 6. NEXT STOP ACTIVATION
                // The immediate next sequence pending stop becomes EN_ROUTE
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

            // Store evidence photo if provided
            if ($evidencePhoto) {
                $this->storeRecordEvidence($serviceRecord, $pic, $evidencePhoto, 'RECEIPT_CONFIRMATION', $notes);
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
    }

    /**
     * Store evidence photo attached to a TripStop.
     */
    protected function storeStopEvidence(TripStop $stop, User $uploader, UploadedFile $file, string $type, ?string $notes = null): EvidencePhoto
    {
        $path = $file->store('evidence_photos/stops', 'local');

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
    protected function storeRecordEvidence(SchoolServiceRecord $record, User $uploader, UploadedFile $file, string $type, ?string $notes = null): EvidencePhoto
    {
        $path = $file->store('evidence_photos/receipts', 'local');

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
