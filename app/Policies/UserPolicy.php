<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view the employee directory.
     */
    public function viewAny(User $currentUser): bool
    {
        return $currentUser->is_active && $currentUser->role === UserRole::KEPALA_SPPG;
    }

    /**
     * Determine whether the user can create an employee account.
     */
    public function create(User $currentUser): bool
    {
        return $currentUser->is_active && $currentUser->role === UserRole::KEPALA_SPPG;
    }

    /**
     * Determine whether the user can view target user details.
     */
    public function view(User $currentUser, User $targetUser): bool
    {
        if (! $currentUser->is_active) {
            return false;
        }

        // Boleh melihat akun sendiri
        if ((int) $currentUser->id === (int) $targetUser->id) {
            return true;
        }

        // Kepala SPPG boleh melihat seluruh pegawai di SPPG yang sama
        if ($currentUser->role === UserRole::KEPALA_SPPG) {
            return (int) $currentUser->sppg_id === (int) $targetUser->getEffectiveSppgId();
        }

        return false;
    }

    /**
     * Determine whether the user can manage (create/update) employee accounts.
     */
    public function manage(User $currentUser): bool
    {
        return $currentUser->is_active && $currentUser->role === UserRole::KEPALA_SPPG;
    }

    /**
     * Determine whether the user can update target employee.
     */
    public function update(User $currentUser, User $targetUser): bool
    {
        if (! $currentUser->is_active || $currentUser->role !== UserRole::KEPALA_SPPG) {
            return false;
        }

        // Hanya boleh mengelola pegawai dalam SPPG yang sama
        return (int) $currentUser->sppg_id === (int) $targetUser->sppg_id;
    }

    /**
     * Determine whether the user can toggle active status of target employee.
     */
    public function toggleActive(User $currentUser, User $targetUser): bool
    {
        if (! $currentUser->is_active || $currentUser->role !== UserRole::KEPALA_SPPG) {
            return false;
        }

        // Hanya boleh mengelola pegawai dalam SPPG yang sama
        return (int) $currentUser->sppg_id === (int) $targetUser->sppg_id;
    }

    /**
     * Determine whether the user can reset password of target employee.
     */
    public function resetPassword(User $currentUser, User $targetUser): bool
    {
        if (! $currentUser->is_active || $currentUser->role !== UserRole::KEPALA_SPPG) {
            return false;
        }

        // Hanya boleh mereset pegawai dalam SPPG yang sama
        return (int) $currentUser->sppg_id === (int) $targetUser->sppg_id;
    }
}
