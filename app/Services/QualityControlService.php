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
     * 2. Organoleptic sensory checklist (appearance, aroma, texture, taste) must be recorded.
     * 3. SAFE conclusion transitions batch to RELEASED and sets released_by / released_at.
     * 4. UNSAFE or HOLD conclusion transitions batch to ON_HOLD and blocks release.
     * 5. When all batches of a plan are RELEASED, transitions plan to READY_FOR_DISTRIBUTION.
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

            // Validate organoleptic checklist
            $appearanceOk = isset($data['appearance_ok']) ? (bool) $data['appearance_ok'] : null;
            $aromaOk = isset($data['aroma_ok']) ? (bool) $data['aroma_ok'] : null;
            $textureOk = isset($data['texture_ok']) ? (bool) $data['texture_ok'] : null;
            $tasteOk = isset($data['taste_ok']) ? (bool) $data['taste_ok'] : null;

            if ($appearanceOk === null || $aromaOk === null || $textureOk === null || $tasteOk === null) {
                throw ValidationException::withMessages([
                    'organoleptic' => ['Seluruh 4 parameter organoleptik (Warna/Penampilan, Aroma, Tekstur, dan Rasa) wajib diperiksa dan diisi.'],
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

            // If conclusion is SAFE, all organoleptic sensory parameters must be OK!
            if ($conclusion === QualityCheckConclusion::SAFE) {
                if (! ($appearanceOk && $aromaOk && $textureOk && $tasteOk)) {
                    throw ValidationException::withMessages([
                        'conclusion' => ['Batch tidak dapat dinyatakan Lolos QC (SAFE) apabila terdapat parameter organoleptik yang tidak memenuhi standar.'],
                    ]);
                }
            }

            $temperatureC = isset($data['temperature_c']) && $data['temperature_c'] !== '' ? (float) $data['temperature_c'] : null;

            $qc = QualityCheck::create([
                'food_batch_id' => $lockedBatch->id,
                'check_stage' => QualityCheckStage::BEFORE_DISPATCH,
                'checked_by' => $actor->id,
                'appearance_ok' => $appearanceOk,
                'aroma_ok' => $aromaOk,
                'texture_ok' => $textureOk,
                'taste_ok' => $tasteOk,
                'temperature_c' => $temperatureC,
                'conclusion' => $conclusion,
                'notes' => $data['notes'] ?? null,
                'checked_at' => now(),
            ]);

            if ($conclusion === QualityCheckConclusion::SAFE) {
                $lockedBatch->update([
                    'status' => FoodBatchStatus::RELEASED,
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
                    ]
                );

                // Check if all batches for this plan are now released
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
                ]);

                ActivityLog::record(
                    event: 'quality_check.held',
                    auditable: $lockedBatch,
                    actor: $actor,
                    newValues: [
                        'conclusion' => $conclusion->value,
                        'notes' => $data['notes'] ?? 'Batch ditahan untuk evaluasi mutu.',
                    ]
                );
            }

            return $qc;
        });
    }
}
