<?php

namespace App\Services;

use App\Enums\FoodBatchStatus;
use App\Enums\ProductionPlanStatus;
use App\Enums\QualityCheckConclusion;
use App\Enums\QualityCheckStage;
use App\Models\ActivityLog;
use App\Models\FoodBatch;
use App\Models\ProductionPlan;
use App\Models\QualityCheck;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QualityControlService
{
    /**
     * Get batches requiring QC inspection or currently on hold for the given SPPG.
     */
    public function getPendingBatchesForQc(int $sppgId): Collection
    {
        return FoodBatch::forSppg($sppgId)
            ->whereIn('status', [FoodBatchStatus::QC_CHECK, FoodBatchStatus::ON_HOLD])
            ->with([
                'productionPlan.menu.items',
                'productionPlan.sppg',
                'creator',
                'evidencePhotos',
                'qualityChecks.inspector',
                'stageLogs',
            ])
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Get all batches for QC monitoring (including released and past batches).
     */
    public function getQcMonitoringList(int $sppgId, ?string $serviceDate = null): Collection
    {
        $query = FoodBatch::forSppg($sppgId)
            ->with([
                'productionPlan.menu.items',
                'productionPlan.sppg',
                'creator',
                'releaser',
                'evidencePhotos',
                'qualityChecks.inspector',
            ])
            ->orderBy('id', 'desc');

        if ($serviceDate) {
            $query->whereHas('productionPlan', function ($q) use ($serviceDate) {
                $q->forDate($serviceDate);
            });
        }

        return $query->get();
    }

    /**
     * Record a quality control inspection decision.
     * Enforces:
     * 1. Actor must belong to the same SPPG as the batch.
     * 2. CRITICAL GATE B: Batch must have a valid FOOD_READY photo or documented exception.
     * 3. CRITICAL GATE A: Organoleptic sensory checklist (appearance, aroma, texture, taste).
     *    Non-forced tasting: If food is suspected of spoilage/contamination, taste test can be skipped with reason.
     *    Temperature check (>60C hot holding) is one parameter, NOT an automatic guarantee of safety.
     * 4. CRITICAL GATE C: Portion reconciliation (accepted_portions <= actual_portions).
     * 5. CRITICAL GATE D: Immutable append-only quality history.
     * 6. SAFE conclusion transitions batch to RELEASED and sets released_by / released_at.
     * 7. UNSAFE or HOLD conclusion transitions batch to ON_HOLD and blocks release.
     * 8. Plan transitions to READY_FOR_DISTRIBUTION only if all batches are RELEASED and accepted.
     */
    public function recordQualityCheck(FoodBatch $batch, array $data, User $actor): QualityCheck
    {
        return DB::transaction(function () use ($batch, $data, $actor) {
            $lockedBatch = FoodBatch::where('id', $batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Tenant isolation
            $plan = ProductionPlan::where('id', $lockedBatch->production_plan_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $plan->sppg_id !== (int) $actor->sppg_id) {
                throw ValidationException::withMessages([
                    'authorization' => ['Anda tidak berwenang melakukan uji mutu batch lintas SPPG.'],
                ]);
            }

            if ($lockedBatch->isReleased()) {
                throw ValidationException::withMessages([
                    'status' => ['Batch ini sudah berstatus Lolos QC & Dirilis (RELEASED). Keputusan terminal tidak dapat diubah tanpa prosedur koreksi.'],
                ]);
            }

            // CRITICAL GATE B: Verify required checkpoint evidence before QC decision
            if (! $lockedBatch->hasValidEvidence()) {
                throw ValidationException::withMessages([
                    'evidence' => ['Pemeriksaan mutu tidak dapat diselesaikan karena batch belum memiliki bukti foto kesiapan makanan (FOOD_READY) atau dokumen pengecualian operasional resmi.'],
                ]);
            }

            // CRITICAL GATE A: Validate sensory organoleptic parameters
            $appearanceOk = isset($data['appearance_ok']) ? (bool) $data['appearance_ok'] : null;
            $aromaOk = isset($data['aroma_ok']) ? (bool) $data['aroma_ok'] : null;
            $textureOk = isset($data['texture_ok']) ? (bool) $data['texture_ok'] : null;
            $tasteTested = isset($data['taste_tested']) ? (bool) $data['taste_tested'] : true;
            $tasteOk = isset($data['taste_ok']) ? (bool) $data['taste_ok'] : null;
            $tasteSkippedReason = trim((string) ($data['taste_skipped_reason'] ?? ''));

            if ($appearanceOk === null || $aromaOk === null || $textureOk === null) {
                throw ValidationException::withMessages([
                    'organoleptic' => ['Parameter organoleptik fisik (Warna/Penampilan, Aroma, dan Tekstur) wajib diperiksa dan diisi.'],
                ]);
            }

            // Safety guard: Non-forced tasting
            if (! $tasteTested && strlen($tasteSkippedReason) < 10) {
                throw ValidationException::withMessages([
                    'taste_skipped_reason' => ['Alasan tidak mencicipi wajib diisi minimal 10 karakter (misal: makanan terindikasi asam menyengat, berlendir, atau dicurigai terkontaminasi).'],
                ]);
            }

            if ($tasteTested && $tasteOk === null) {
                throw ValidationException::withMessages([
                    'taste_ok' => ['Hasil uji rasa organoleptik wajib ditentukan jika pengujian rasa dilakukan.'],
                ]);
            }

            // Conclusion validation
            $conclusionStr = (string) ($data['conclusion'] ?? '');
            $conclusion = QualityCheckConclusion::tryFrom($conclusionStr);

            if (! $conclusion) {
                throw ValidationException::withMessages([
                    'conclusion' => ['Keputusan kesimpulan mutu tidak valid. Pilih SAFE, UNSAFE, atau HOLD.'],
                ]);
            }

            // Food Safety Rule: If conclusion is SAFE:
            // 1. All sensory physical parameters MUST be OK.
            // 2. Taste test MUST have been safely performed AND confirmed OK.
            // 3. Tasting cannot be skipped due to spoilage if claiming SAFE.
            if ($conclusion === QualityCheckConclusion::SAFE) {
                if (! ($appearanceOk && $aromaOk && $textureOk)) {
                    throw ValidationException::withMessages([
                        'conclusion' => ['Batch tidak dapat dinyatakan Lolos QC (SAFE) apabila terdapat parameter fisik (penampilan, aroma, atau tekstur) yang tidak memenuhi standar.'],
                    ]);
                }

                if (! $tasteTested) {
                    throw ValidationException::withMessages([
                        'conclusion' => ['Batch tidak dapat dinyatakan Lolos QC (SAFE) jika uji rasa dilewati karena kecurigaan kontaminasi atau pembusukan.'],
                    ]);
                }

                if (! $tasteOk) {
                    throw ValidationException::withMessages([
                        'conclusion' => ['Batch tidak dapat dinyatakan Lolos QC (SAFE) jika rasa makanan tidak memenuhi standar kelayakan konsumsi.'],
                    ]);
                }
            }

            // CRITICAL GATE C: Portion reconciliation
            $actualPortions = (int) ($lockedBatch->actual_portions ?? 0);
            $acceptedPortions = null;
            $rejectedPortions = 0;

            if ($conclusion === QualityCheckConclusion::SAFE) {
                $requestedAccepted = isset($data['accepted_portions']) && $data['accepted_portions'] !== ''
                    ? (int) $data['accepted_portions']
                    : $actualPortions;

                if ($requestedAccepted < 0) {
                    throw ValidationException::withMessages([
                        'accepted_portions' => ['Jumlah porsi lolos QC (accepted) tidak boleh bernilai negatif.'],
                    ]);
                }

                if ($requestedAccepted > $actualPortions) {
                    throw ValidationException::withMessages([
                        'accepted_portions' => ["Jumlah porsi lolos QC ({$requestedAccepted} porsi) tidak boleh melebihi realisasi produksi aktual ({$actualPortions} porsi)."],
                    ]);
                }

                $acceptedPortions = $requestedAccepted;
                $rejectedPortions = max(0, $actualPortions - $acceptedPortions);
            } else {
                // HOLD or UNSAFE -> 0 accepted portions
                $acceptedPortions = 0;
                $rejectedPortions = $actualPortions;
            }

            $temperatureC = isset($data['temperature_c']) && $data['temperature_c'] !== '' ? (float) $data['temperature_c'] : null;
            $sopReference = trim((string) ($data['sop_reference'] ?? 'SOP-BGN-MUTU-01'));
            $decisionReason = trim((string) ($data['decision_reason'] ?? ''));

            if (strlen($decisionReason) < 10) {
                throw ValidationException::withMessages([
                    'decision_reason' => ['Alasan dan pertimbangan keputusan Pengawas Gizi wajib dicatat minimal 10 karakter untuk rekam jejak audit.'],
                ]);
            }

            // Create append-only QualityCheck audit record
            $qc = QualityCheck::create([
                'food_batch_id' => $lockedBatch->id,
                'check_stage' => QualityCheckStage::BEFORE_DISPATCH,
                'checked_by' => $actor->id,
                'appearance_ok' => $appearanceOk,
                'aroma_ok' => $aromaOk,
                'texture_ok' => $textureOk,
                'taste_tested' => $tasteTested,
                'taste_ok' => $tasteTested ? $tasteOk : null,
                'taste_skipped_reason' => ! $tasteTested ? $tasteSkippedReason : null,
                'temperature_c' => $temperatureC,
                'accepted_portions' => $acceptedPortions,
                'rejected_portions' => $rejectedPortions,
                'sop_reference' => $sopReference ?: 'SOP-BGN-MUTU-01',
                'decision_reason' => $decisionReason,
                'conclusion' => $conclusion,
                'notes' => $data['notes'] ?? null,
                'checked_at' => now(),
            ]);

            if ($conclusion === QualityCheckConclusion::SAFE) {
                $lockedBatch->update([
                    'status' => FoodBatchStatus::RELEASED,
                    'accepted_portions' => $acceptedPortions,
                    'released_by' => $actor->id,
                    'released_at' => now(),
                ]);

                ActivityLog::record(
                    event: 'quality_check.released',
                    auditable: $lockedBatch,
                    actor: $actor,
                    newValues: [
                        'conclusion' => QualityCheckConclusion::SAFE->value,
                        'released_by' => $actor->id,
                        'released_at' => $lockedBatch->released_at->toIso8601String(),
                        'actual_portions' => $lockedBatch->actual_portions,
                        'accepted_portions' => $acceptedPortions,
                        'rejected_portions' => $rejectedPortions,
                        'temperature_c' => $temperatureC,
                        'sop_reference' => $sopReference,
                        'decision_reason' => $decisionReason,
                    ]
                );

                // Check if all batches for this plan are released and portions meet the plan
                $unreleasedCount = FoodBatch::where('production_plan_id', $plan->id)
                    ->where('status', '!=', FoodBatchStatus::RELEASED->value)
                    ->count();

                if ($unreleasedCount === 0) {
                    $plan->update(['status' => ProductionPlanStatus::READY_FOR_DISTRIBUTION]);

                    ActivityLog::record(
                        event: 'production_plan.ready_for_distribution',
                        auditable: $plan,
                        actor: $actor,
                        newValues: [
                            'status' => ProductionPlanStatus::READY_FOR_DISTRIBUTION->value,
                            'all_batches_released' => true,
                        ]
                    );
                }
            } else {
                // UNSAFE or HOLD -> Put on hold
                $lockedBatch->update([
                    'status' => FoodBatchStatus::ON_HOLD,
                    'accepted_portions' => 0,
                ]);

                ActivityLog::record(
                    event: 'quality_check.held',
                    auditable: $lockedBatch,
                    actor: $actor,
                    newValues: [
                        'conclusion' => $conclusion->value,
                        'decision_reason' => $decisionReason,
                        'rejected_portions' => $rejectedPortions,
                        'notes' => $data['notes'] ?? 'Batch ditahan untuk evaluasi mutu.',
                    ]
                );
            }

            return $qc;
        });
    }
}
