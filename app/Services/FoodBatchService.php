<?php

namespace App\Services;

use App\Enums\FoodBatchStatus;
use App\Enums\ProductionPlanStatus;
use App\Enums\ProductionStage;
use App\Models\ActivityLog;
use App\Models\DeliveryItem;
use App\Models\EvidencePhoto;
use App\Models\FoodBatch;
use App\Models\ProductionPlan;
use App\Models\ProductionStageLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FoodBatchService
{
    /**
     * Create a new food batch from a FINALIZED or IN_PRODUCTION plan.
     * Enforces:
     * 1. Plan status must be FINALIZED or IN_PRODUCTION.
     * 2. Tenant isolation (actor SPPG === plan SPPG).
     * 3. Target portions must be positive.
     * 4. CRITICAL GATE C: Cumulative planned portions across all batches for this plan
     *    cannot exceed the plan's total allocated portions.
     * 5. PBI-07: Automatically establishes DeliveryItem links to school allocations.
     */
    public function createBatch(ProductionPlan $plan, array $data, User $actor): FoodBatch
    {
        return DB::transaction(function () use ($plan, $data, $actor) {
            $lockedPlan = ProductionPlan::where('id', $plan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedPlan->status, [ProductionPlanStatus::FINALIZED, ProductionPlanStatus::IN_PRODUCTION], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Batch hanya dapat dibuat untuk rencana produksi yang telah Difinalisasi (FINALIZED) atau Sedang Berjalan (IN_PRODUCTION).'],
                ]);
            }

            if ((int) $lockedPlan->sppg_id !== (int) $actor->sppg_id) {
                throw ValidationException::withMessages([
                    'authorization' => ['Anda tidak berwenang membuat batch produksi lintas SPPG.'],
                ]);
            }

            $plannedPortions = (int) ($data['planned_portions'] ?? 0);
            if ($plannedPortions <= 0) {
                throw ValidationException::withMessages([
                    'planned_portions' => ['Target jumlah porsi batch harus bernilai lebih dari nol.'],
                ]);
            }

            // CRITICAL GATE C: Enforce batch target accumulation against plan allocation
            $currentPlannedSum = (int) FoodBatch::where('production_plan_id', $lockedPlan->id)->sum('planned_portions');
            $totalAllocated = $lockedPlan->totalAllocatedPortions();

            if ($currentPlannedSum + $plannedPortions > $totalAllocated) {
                $availableRemaining = max(0, $totalAllocated - $currentPlannedSum);
                throw ValidationException::withMessages([
                    'planned_portions' => [
                        "Akumulasi target porsi batch (" . ($currentPlannedSum + $plannedPortions) . " porsi) melebihi total alokasi rencana produksi ({$totalAllocated} porsi). Sisa porsi yang belum direncanakan ke batch adalah {$availableRemaining} porsi."
                    ],
                ]);
            }

            // Generate unique batch code: BATCH-YYYYMMDD-SPPG-XX
            $datePrefix = $lockedPlan->service_date->format('Ymd');
            $sppgCode = Str::slug($lockedPlan->sppg->code ?? 'SPPG');
            $sequence = FoodBatch::where('production_plan_id', $lockedPlan->id)->count() + 1;
            $batchCode = strtoupper(sprintf('BATCH-%s-%s-%02d', $datePrefix, $sppgCode, $sequence));

            // Ensure unique batch code
            while (FoodBatch::where('batch_code', $batchCode)->exists()) {
                $batchCode = strtoupper(sprintf('BATCH-%s-%s-%02d-%s', $datePrefix, $sppgCode, $sequence, Str::random(4)));
            }

            $batch = FoodBatch::create([
                'production_plan_id' => $lockedPlan->id,
                'menu_id' => $lockedPlan->menu_id,
                'batch_code' => $batchCode,
                'portion_category' => $data['portion_category'] ?? 'STANDARD',
                'planned_portions' => $plannedPortions,
                'status' => FoodBatchStatus::PREPARING,
                'created_by' => $actor->id,
                'notes' => $data['notes'] ?? null,
            ]);

            // Start initial PREPARATION stage log
            ProductionStageLog::create([
                'food_batch_id' => $batch->id,
                'stage' => ProductionStage::PREPARATION,
                'started_at' => now(),
                'updated_by' => $actor->id,
                'notes' => 'Mulai persiapan bahan masakan.',
            ]);

            // PBI-07: Establish bidirectional DeliveryItem links to school allocations
            $this->linkBatchToAllocations($batch, $lockedPlan, $plannedPortions);

            // Transition plan status from FINALIZED -> IN_PRODUCTION
            if ($lockedPlan->status === ProductionPlanStatus::FINALIZED) {
                $lockedPlan->update(['status' => ProductionPlanStatus::IN_PRODUCTION]);

                ActivityLog::record(
                    event: 'production_plan.in_production',
                    auditable: $lockedPlan,
                    actor: $actor,
                    oldValues: ['status' => ProductionPlanStatus::FINALIZED->value],
                    newValues: ['status' => ProductionPlanStatus::IN_PRODUCTION->value]
                );
            }

            ActivityLog::record(
                event: 'food_batch.created',
                auditable: $batch,
                actor: $actor,
                newValues: [
                    'batch_code' => $batch->batch_code,
                    'planned_portions' => $batch->planned_portions,
                    'status' => FoodBatchStatus::PREPARING->value,
                ]
            );

            return $batch;
        });
    }

    /**
     * Link portions from a batch to the production plan's school allocations.
     * Ensures bidirectional traceability: Batch -> DeliveryItem -> School.
     */
    protected function linkBatchToAllocations(FoodBatch $batch, ProductionPlan $plan, int $plannedPortions): void
    {
        $remainingToDistribute = $plannedPortions;
        $allocations = $plan->allocations()->orderBy('id', 'asc')->get();

        foreach ($allocations as $allocation) {
            if ($remainingToDistribute <= 0) {
                break;
            }

            $alreadyLinked = (int) DeliveryItem::where('production_allocation_id', $allocation->id)->sum('planned_portions');
            $neededForThisSchool = max(0, (int) $allocation->allocated_portions - $alreadyLinked);

            if ($neededForThisSchool > 0) {
                $amount = min($remainingToDistribute, $neededForThisSchool);
                DeliveryItem::create([
                    'food_batch_id' => $batch->id,
                    'production_allocation_id' => $allocation->id,
                    'trip_stop_id' => null, // Pending dispatch in PBI-08
                    'planned_portions' => $amount,
                    'status' => 'PENDING_DISPATCH',
                ]);
                $remainingToDistribute -= $amount;
            }
        }

        // Fallback: If any portion remains (e.g. all quotas filled or rounding), link to first allocation
        if ($remainingToDistribute > 0 && $allocations->isNotEmpty()) {
            $firstAlloc = $allocations->first();
            $existing = DeliveryItem::where('food_batch_id', $batch->id)
                ->where('production_allocation_id', $firstAlloc->id)
                ->first();

            if ($existing) {
                $existing->increment('planned_portions', $remainingToDistribute);
            } else {
                DeliveryItem::create([
                    'food_batch_id' => $batch->id,
                    'production_allocation_id' => $firstAlloc->id,
                    'trip_stop_id' => null,
                    'planned_portions' => $remainingToDistribute,
                    'status' => 'PENDING_DISPATCH',
                ]);
            }
        }
    }

    /**
     * Advance batch to the next production stage in strict sequence:
     * PREPARATION -> COOKING -> PORTIONING -> PACKING.
     */
    public function advanceStage(FoodBatch $batch, ProductionStage $targetStage, ?string $notes, User $actor): FoodBatch
    {
        return DB::transaction(function () use ($batch, $targetStage, $notes, $actor) {
            $lockedBatch = FoodBatch::where('id', $batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBatch->isReleased() || $lockedBatch->isOnHold()) {
                throw ValidationException::withMessages([
                    'status' => ['Tahapan batch tidak dapat diubah karena batch berstatus ' . $lockedBatch->status->label() . '.'],
                ]);
            }

            $currentLog = $lockedBatch->currentStageLog();
            $currentStage = $currentLog ? $currentLog->stage : ProductionStage::PREPARATION;

            // Enforce sequential stage progress
            $expectedNext = $currentStage->next();
            if ($expectedNext !== $targetStage) {
                throw ValidationException::withMessages([
                    'stage' => [
                        "Urutan tahapan produksi tidak boleh dilompati. Tahap berikutnya yang sah adalah: " . ($expectedNext ? $expectedNext->label() : 'Selesai'),
                    ],
                ]);
            }

            // Close current stage log
            if ($currentLog && ! $currentLog->isCompleted()) {
                $currentLog->update([
                    'completed_at' => now(),
                ]);
            }

            // Map target stage to batch status
            $newStatus = match ($targetStage) {
                ProductionStage::COOKING => FoodBatchStatus::COOKING,
                ProductionStage::PORTIONING => FoodBatchStatus::PORTIONING,
                ProductionStage::PACKING => FoodBatchStatus::PACKED,
                ProductionStage::READY_FOR_QC => FoodBatchStatus::QC_CHECK,
                default => $lockedBatch->status,
            };

            $lockedBatch->update(['status' => $newStatus]);

            // Open new stage log
            ProductionStageLog::create([
                'food_batch_id' => $lockedBatch->id,
                'stage' => $targetStage,
                'started_at' => now(),
                'updated_by' => $actor->id,
                'notes' => $notes,
            ]);

            ActivityLog::record(
                event: 'food_batch.stage_advanced',
                auditable: $lockedBatch,
                actor: $actor,
                newValues: [
                    'stage' => $targetStage->value,
                    'status' => $newStatus->value,
                    'notes' => $notes,
                ]
            );

            return $lockedBatch->fresh(['stageLogs', 'qualityChecks', 'evidencePhotos', 'deliveryItems']);
        });
    }

    /**
     * Record actual produced portions.
     * Enforces:
     * 1. Cannot modify if batch is in QC_CHECK, ON_HOLD, or RELEASED.
     * 2. Actual portions must be non-negative.
     */
    public function recordActualPortions(FoodBatch $batch, int $actualPortions, ?string $notes, User $actor): FoodBatch
    {
        return DB::transaction(function () use ($batch, $actualPortions, $notes, $actor) {
            $lockedBatch = FoodBatch::where('id', $batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($lockedBatch->status, [FoodBatchStatus::QC_CHECK, FoodBatchStatus::ON_HOLD, FoodBatchStatus::RELEASED], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Realisasi porsi tidak dapat diubah karena batch telah diserahkan ke QC atau berstatus ' . $lockedBatch->status->label() . '.'],
                ]);
            }

            if ($actualPortions < 0) {
                throw ValidationException::withMessages([
                    'actual_portions' => ['Realisasi jumlah porsi aktual tidak boleh bernilai negatif.'],
                ]);
            }

            $oldActual = $lockedBatch->actual_portions;

            $lockedBatch->update([
                'actual_portions' => $actualPortions,
                'notes' => $notes ?? $lockedBatch->notes,
            ]);

            ActivityLog::record(
                event: 'food_batch.actual_portions_recorded',
                auditable: $lockedBatch,
                actor: $actor,
                oldValues: ['actual_portions' => $oldActual],
                newValues: [
                    'actual_portions' => $actualPortions,
                    'planned_portions' => $lockedBatch->planned_portions,
                    'discrepancy' => $actualPortions - $lockedBatch->planned_portions,
                ]
            );

            return $lockedBatch;
        });
    }

    /**
     * Submit batch to Quality Control (Pengawas Gizi).
     * Requires:
     * 1. Actual portions is recorded.
     * 2. Batch is not already RELEASED.
     * 3. CRITICAL GATE B: FOOD_READY photo is normally REQUIRED, or an approved reasoned exception must be present.
     * 4. Private storage with UUID filename, validated MIME and size.
     * 5. Transitions status to QC_CHECK.
     */
    public function submitToQc(FoodBatch $batch, ?UploadedFile $photo, ?string $notes, User $actor, array $exceptionData = []): FoodBatch
    {
        return DB::transaction(function () use ($batch, $photo, $notes, $actor, $exceptionData) {
            $lockedBatch = FoodBatch::where('id', $batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBatch->actual_portions === null) {
                throw ValidationException::withMessages([
                    'actual_portions' => ['Realisasi jumlah porsi aktual wajib dicatat sebelum menyerahkan batch ke Pemeriksaan Mutu (QC).'],
                ]);
            }

            if ($lockedBatch->isReleased()) {
                throw ValidationException::withMessages([
                    'status' => ['Batch ini sudah diperiksa dan berstatus Lolos QC.'],
                ]);
            }

            // CRITICAL GATE B: Evidence Photo verification & Reasoned Exception
            $hasPhoto = $photo && $photo->isValid();
            $hasException = ! empty($exceptionData['has_evidence_exception']) && (bool) $exceptionData['has_evidence_exception'];
            $exceptionReason = trim((string) ($exceptionData['evidence_exception_reason'] ?? ''));

            if (! $hasPhoto && ! $hasException) {
                throw ValidationException::withMessages([
                    'photo' => ['Foto bukti kesiapan makanan (FOOD_READY) wajib diunggah pada akhir pengemasan, atau berikan alasan pengecualian operasional resmi minimal 15 karakter jika terdapat kendala kamera.'],
                ]);
            }

            if (! $hasPhoto && $hasException && strlen($exceptionReason) < 15) {
                throw ValidationException::withMessages([
                    'evidence_exception_reason' => ['Alasan pengecualian bukti foto wajib diisi minimal 15 karakter untuk kebutuhan audit operasional.'],
                ]);
            }

            // Close current stage log if packing
            $currentLog = $lockedBatch->currentStageLog();
            if ($currentLog && ! $currentLog->isCompleted()) {
                $currentLog->update(['completed_at' => now()]);
            }

            // Handle photo upload if present
            if ($hasPhoto) {
                // Validate mime type strictly against supported formats
                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
                $clientMime = $photo->getMimeType() ?: $photo->getClientMimeType();

                if (! in_array($clientMime, $allowedMimes, true)) {
                    throw ValidationException::withMessages([
                        'photo' => ['Format berkas foto tidak didukung. Gunakan berkas gambar asli dengan format JPEG, PNG, atau WEBP.'],
                    ]);
                }

                if ($photo->getSize() > 5 * 1024 * 1024) {
                    throw ValidationException::withMessages([
                        'photo' => ['Ukuran berkas foto melebihi batas maksimal 5 MB.'],
                    ]);
                }

                // Generate sanitized UUID filename
                $extension = strtolower($photo->getClientOriginalExtension()) ?: 'jpg';
                $safeFileName = sprintf('%s.%s', Str::uuid()->toString(), $extension);
                $path = $photo->storeAs('evidence_photos/food_ready', $safeFileName, 'local');

                EvidencePhoto::create([
                    'evidenceable_type' => FoodBatch::class,
                    'evidenceable_id' => $lockedBatch->id,
                    'evidence_type' => 'FOOD_READY',
                    'uploaded_by' => $actor->id,
                    'file_path' => $path,
                    'mime_type' => $clientMime,
                    'file_size' => $photo->getSize(),
                    'captured_at' => now(),
                    'notes' => $notes ?? 'Foto kesiapan makanan sebelum QC dispatch.',
                ]);
            } elseif ($hasException) {
                // Record documented exception
                $lockedBatch->update([
                    'has_evidence_exception' => true,
                    'evidence_exception_reason' => $exceptionReason,
                    'evidence_exception_approved_by' => $actor->id,
                ]);
            }

            // Transition status to QC_CHECK
            $lockedBatch->update([
                'status' => FoodBatchStatus::QC_CHECK,
            ]);

            // Create stage log for QC handover
            ProductionStageLog::create([
                'food_batch_id' => $lockedBatch->id,
                'stage' => ProductionStage::READY_FOR_QC,
                'started_at' => now(),
                'updated_by' => $actor->id,
                'notes' => $notes ?? ($hasException ? 'Batch diserahkan ke QC dengan pengecualian bukti foto.' : 'Batch diserahkan kepada Pengawas Gizi untuk pemeriksaan organoleptik.'),
            ]);

            ActivityLog::record(
                event: 'food_batch.submitted_to_qc',
                auditable: $lockedBatch,
                actor: $actor,
                newValues: [
                    'status' => FoodBatchStatus::QC_CHECK->value,
                    'actual_portions' => $lockedBatch->actual_portions,
                    'has_evidence' => $hasPhoto,
                    'has_exception' => $hasException,
                ]
            );

            return $lockedBatch->fresh(['stageLogs', 'qualityChecks', 'evidencePhotos', 'deliveryItems']);
        });
    }
}
