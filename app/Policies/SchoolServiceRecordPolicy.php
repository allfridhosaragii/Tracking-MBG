<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SchoolServiceRecord;
use App\Models\User;

class SchoolServiceRecordPolicy
{
    /**
     * Determine whether the user can view the service record.
     */
    public function view(User $user, SchoolServiceRecord $record): bool
    {
        if (!$user->is_active) {
            return false;
        }

        $schoolId = $record->tripStop?->school_id;
        $sppgId = $record->tripStop?->trip?->sppg_id;

        if ($user->role === UserRole::PIC_SEKOLAH) {
            return (int) $user->school_id === (int) $schoolId;
        }

        // Internal SPPG roles can inspect within their SPPG scope
        return (int) $user->sppg_id === (int) $sppgId;
    }

    /**
     * Determine whether the user can conduct pre-consumption check,
     * start distribution, or complete meal (Page P05).
     * Strictly restricted to the authorized school's PIC.
     */
    public function manageMeal(User $user, SchoolServiceRecord $record): bool
    {
        if (!$user->is_active || $user->role !== UserRole::PIC_SEKOLAH) {
            return false;
        }

        return (int) $user->school_id === (int) $record->tripStop?->school_id;
    }
}
