<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\DailyPortionRequest;
use App\Models\School;
use App\Models\User;

class DailyPortionRequestPolicy
{
    /**
     * Determine whether the user can view any portion requests.
     */
    public function viewAny(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->hasRole(UserRole::PIC_SEKOLAH)
            || $user->hasRole(UserRole::KEPALA_SPPG)
            || $user->hasRole(UserRole::PENGAWAS_GIZI)
            || $user->hasRole(UserRole::ASISTEN_LAPANGAN);
    }

    /**
     * Determine whether the user can view the specific portion request.
     */
    public function view(User $user, DailyPortionRequest $portionRequest): bool
    {
        if (! $user->is_active) {
            return false;
        }

        // PIC Sekolah: Hanya boleh melihat data sekolah binaannya sendiri
        if ($user->hasRole(UserRole::PIC_SEKOLAH)) {
            return (int) $user->school_id === (int) $portionRequest->school_id;
        }

        // Staf SPPG Internal: Hanya boleh melihat sekolah yang dilayani SPPG tempat bertugas
        if ($user->isInternalStaff()) {
            return (int) $user->getEffectiveSppgId() === (int) $portionRequest->school?->sppg_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create portion requests for a school.
     */
    public function create(User $user, School $school): bool
    {
        return $user->is_active
            && $user->hasRole(UserRole::PIC_SEKOLAH)
            && (int) $user->school_id === (int) $school->id;
    }

    /**
     * Determine whether the user can update the draft portion request.
     */
    public function update(User $user, DailyPortionRequest $portionRequest): bool
    {
        return $user->is_active
            && $user->hasRole(UserRole::PIC_SEKOLAH)
            && (int) $user->school_id === (int) $portionRequest->school_id
            && $portionRequest->isDraft();
    }

    /**
     * Determine whether the user can confirm the portion request.
     */
    public function confirm(User $user, DailyPortionRequest $portionRequest): bool
    {
        return $user->is_active
            && $user->hasRole(UserRole::PIC_SEKOLAH)
            && (int) $user->school_id === (int) $portionRequest->school_id
            && ($portionRequest->isDraft() || $portionRequest->isConfirmed());
    }

    /**
     * Determine whether the user can submit a revision.
     */
    public function revise(User $user, DailyPortionRequest $portionRequest): bool
    {
        if (! $user->is_active || ! $portionRequest->canBeRevised()) {
            return false;
        }

        // PIC sekolahnya sendiri
        if ($user->hasRole(UserRole::PIC_SEKOLAH) && (int) $user->school_id === (int) $portionRequest->school_id) {
            return true;
        }

        // Kepala SPPG untuk sekolah di wilayah SPPG-nya
        if ($user->hasRole(UserRole::KEPALA_SPPG) && (int) $user->sppg_id === (int) $portionRequest->school?->sppg_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can cancel the portion request.
     */
    public function cancel(User $user, DailyPortionRequest $portionRequest): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->hasRole(UserRole::PIC_SEKOLAH) && (int) $user->school_id === (int) $portionRequest->school_id) {
            return true;
        }

        if ($user->hasRole(UserRole::KEPALA_SPPG) && (int) $user->sppg_id === (int) $portionRequest->school?->sppg_id) {
            return true;
        }

        return false;
    }
}
