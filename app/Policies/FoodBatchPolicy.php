<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\FoodBatch;
use App\Models\ProductionPlan;
use App\Models\User;

class FoodBatchPolicy
{
    /**
     * Determine whether the user can view any batches.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            UserRole::KEPALA_SPPG,
            UserRole::ASISTEN_LAPANGAN,
            UserRole::PENGAWAS_GIZI,
        ], true);
    }

    /**
     * Determine whether the user can view the batch.
     */
    public function view(User $user, FoodBatch $batch): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        $sppgId = $batch->productionPlan ? (int) $batch->productionPlan->sppg_id : 0;
        return (int) $user->sppg_id === $sppgId;
    }

    /**
     * Determine whether the user can create a batch for a production plan.
     * Only Asisten Lapangan in the same SPPG can create batches.
     */
    public function create(User $user, ProductionPlan $plan): bool
    {
        return $user->role === UserRole::ASISTEN_LAPANGAN
            && (int) $user->sppg_id === (int) $plan->sppg_id;
    }

    /**
     * Determine whether the user can advance production stages.
     * Only Asisten Lapangan in the same SPPG.
     */
    public function advanceStage(User $user, FoodBatch $batch): bool
    {
        $sppgId = $batch->productionPlan ? (int) $batch->productionPlan->sppg_id : 0;
        return $user->role === UserRole::ASISTEN_LAPANGAN
            && (int) $user->sppg_id === $sppgId;
    }

    /**
     * Determine whether the user can record actual portions.
     * Only Asisten Lapangan in the same SPPG.
     */
    public function recordActual(User $user, FoodBatch $batch): bool
    {
        $sppgId = $batch->productionPlan ? (int) $batch->productionPlan->sppg_id : 0;
        return $user->role === UserRole::ASISTEN_LAPANGAN
            && (int) $user->sppg_id === $sppgId;
    }

    /**
     * Determine whether the user can submit a batch to QC.
     * Only Asisten Lapangan in the same SPPG.
     */
    public function submitQc(User $user, FoodBatch $batch): bool
    {
        $sppgId = $batch->productionPlan ? (int) $batch->productionPlan->sppg_id : 0;
        return $user->role === UserRole::ASISTEN_LAPANGAN
            && (int) $user->sppg_id === $sppgId;
    }

    /**
     * Determine whether the user can perform quality control inspections.
     * Only Pengawas Gizi in the same SPPG.
     */
    public function performQc(User $user, FoodBatch $batch): bool
    {
        $sppgId = $batch->productionPlan ? (int) $batch->productionPlan->sppg_id : 0;
        return $user->role === UserRole::PENGAWAS_GIZI
            && (int) $user->sppg_id === $sppgId;
    }
}
