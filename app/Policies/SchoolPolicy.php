<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\User;

class SchoolPolicy
{
    /**
     * Determine whether the user can view the school data.
     */
    public function view(User $user, School $school): bool
    {
        if (! $user->is_active) {
            return false;
        }

        // PIC Sekolah: hanya boleh melihat sekolah miliknya sendiri
        if ($user->role === UserRole::PIC_SEKOLAH) {
            return (int) $user->school_id === (int) $school->id;
        }

        // Staf Internal SPPG (Kepala, Gizi, Asisten, Kurir):
        // Hanya boleh melihat sekolah yang dilayani oleh SPPG tempat ia bertugas
        if ($user->isInternalStaff()) {
            return (int) $user->getEffectiveSppgId() === (int) $school->sppg_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create schools.
     */
    public function create(User $user): bool
    {
        return $user->is_active && $user->role === UserRole::KEPALA_SPPG;
    }

    /**
     * Determine whether the user can update the school.
     */
    public function update(User $user, School $school): bool
    {
        return $user->is_active
            && $user->role === UserRole::KEPALA_SPPG
            && (int) $user->sppg_id === (int) $school->sppg_id;
    }

    /**
     * Determine whether the user can delete the school.
     */
    public function delete(User $user, School $school): bool
    {
        // Berdasarkan kontrak PBI-01: Sekolah berhistori dinonaktifkan, bukan hard-delete
        return false;
    }
}
