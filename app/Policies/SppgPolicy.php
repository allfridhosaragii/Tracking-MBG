<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Sppg;
use App\Models\User;

class SppgPolicy
{
    /**
     * Determine whether the user can view the SPPG.
     */
    public function view(User $user, Sppg $sppg): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return (int) $user->getEffectiveSppgId() === (int) $sppg->id;
    }

    /**
     * Determine whether the user can update the SPPG.
     */
    public function update(User $user, Sppg $sppg): bool
    {
        return $user->is_active
            && $user->role === UserRole::KEPALA_SPPG
            && (int) $user->sppg_id === (int) $sppg->id;
    }
}
