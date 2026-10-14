<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\TripStop;
use App\Models\User;

class TripStopPolicy
{
    /**
     * Determine whether the user can view the trip stop details.
     */
    public function view(User $user, TripStop $stop): bool
    {
        if (!$user->is_active) {
            return false;
        }

        // SPPG Staff
        if (in_array($user->role, [UserRole::KEPALA_SPPG, UserRole::ASISTEN_LAPANGAN, UserRole::PENGAWAS_GIZI], true)) {
            return (int) $user->sppg_id === (int) $stop->trip->sppg_id;
        }

        // Assigned Courier
        if ($user->role === UserRole::KURIR) {
            return (int) $user->id === (int) $stop->trip->driver_id;
        }

        // PIC Sekolah for this specific school
        if ($user->role === UserRole::PIC_SEKOLAH) {
            return (int) $user->school_id === (int) $stop->school_id;
        }

        return false;
    }

    /**
     * Determine whether the user (assigned courier) can mark arrival at the stop.
     */
    public function arrive(User $user, TripStop $stop): bool
    {
        return $user->is_active
            && $user->role === UserRole::KURIR
            && (int) $user->id === (int) $stop->trip->driver_id
            && (int) $user->sppg_id === (int) $stop->trip->sppg_id;
    }

    /**
     * Determine whether the user can view receipt details / history (Page P04 read-only).
     * PIC can always view their school's receipt history, even when HANDED_OVER, HELD, REJECTED, or EXCEPTION.
     */
    public function viewReceipt(User $user, TripStop $stop): bool
    {
        if (!$user->is_active) {
            return false;
        }

        // PIC Sekolah for this specific school
        if ($user->role === UserRole::PIC_SEKOLAH) {
            return $user->school_id !== null && (int) $user->school_id === (int) $stop->school_id;
        }

        // Assigned Courier
        if ($user->role === UserRole::KURIR) {
            return (int) $user->id === (int) $stop->trip->driver_id;
        }

        // Internal SPPG Staff
        if (in_array($user->role, [UserRole::KEPALA_SPPG, UserRole::ASISTEN_LAPANGAN, UserRole::PENGAWAS_GIZI], true)) {
            return $user->sppg_id !== null && (int) $user->sppg_id === (int) $stop->trip->sppg_id;
        }

        return false;
    }

    /**
     * Determine whether the user (PIC Sekolah) can verify and submit receipt confirmation.
     * Enforces strict separation of duties (courier CANNOT self-approve),
     * stop arrival precondition, and locks write actions when stop is in EXCEPTION or COMPLETED.
     */
    public function confirmReceipt(User $user, TripStop $stop): bool
    {
        if (!$user->is_active || $user->role !== UserRole::PIC_SEKOLAH) {
            return false;
        }

        if ($user->school_id === null || (int) $user->school_id !== (int) $stop->school_id) {
            return false;
        }

        // Courier cannot self-confirm on behalf of school
        if ((int) $user->id === (int) $stop->trip->driver_id) {
            return false;
        }

        // If stop is in EXCEPTION, REJECTED, or COMPLETED, write actions are strictly locked
        if (in_array($stop->status, [
            \App\Enums\TripStopStatus::EXCEPTION,
            \App\Enums\TripStopStatus::COMPLETED,
            \App\Enums\TripStopStatus::SKIPPED,
        ], true)) {
            return false;
        }

        // Allow when stop is arrived or handed over (supports idempotent retry / double-submit)
        return in_array($stop->status, [
            \App\Enums\TripStopStatus::ARRIVED,
            \App\Enums\TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
            \App\Enums\TripStopStatus::HANDED_OVER,
        ], true);
    }
}


