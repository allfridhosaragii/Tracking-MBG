<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ProductionPlan;
use App\Models\User;

class ProductionPlanPolicy
{
    /**
     * Determine whether the user can view any production plans.
     */
    public function viewAny(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->hasRole(UserRole::KEPALA_SPPG) || $user->hasRole(UserRole::ASISTEN_LAPANGAN);
    }

    /**
     * Determine whether the user can view the specific production plan.
     */
    public function view(User $user, ProductionPlan $plan): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ((int) $user->getEffectiveSppgId() !== (int) $plan->sppg_id) {
            return false;
        }

        if ($user->hasRole(UserRole::KEPALA_SPPG)) {
            return true;
        }

        if ($user->hasRole(UserRole::ASISTEN_LAPANGAN)) {
            // Asisten Lapangan only reads finalized or active plans
            return $plan->isFinalized() || $plan->status->isLocked();
        }

        return false;
    }

    /**
     * Determine whether the user can create production plans.
     */
    public function create(User $user): bool
    {
        return $user->is_active && $user->hasRole(UserRole::KEPALA_SPPG);
    }

    /**
     * Determine whether the user can finalize the production plan.
     */
    public function finalize(User $user, ProductionPlan $plan): bool
    {
        return $user->is_active
            && $user->hasRole(UserRole::KEPALA_SPPG)
            && (int) $user->getEffectiveSppgId() === (int) $plan->sppg_id
            && ($plan->canBeFinalized() || $plan->isFinalized());
    }

    /**
     * Determine whether the user can cancel the production plan.
     */
    public function cancel(User $user, ProductionPlan $plan): bool
    {
        return $user->is_active
            && $user->hasRole(UserRole::KEPALA_SPPG)
            && (int) $user->getEffectiveSppgId() === (int) $plan->sppg_id
            && ! $plan->status->isLocked();
    }
}
