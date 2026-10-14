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
     * Determine whether the user (PIC Sekolah) can verify and confirm receipt of food.
     * Enforces strict separation of duties: courier CANNOT self-approve,
     * and receipt cannot occur before the vehicle has actually arrived.
     */
    public function confirmReceipt(User $user, TripStop $stop): bool
    {
        return $user->is_active
            && $user->role === UserRole::PIC_SEKOLAH
            && (int) $user->school_id === (int) $stop->school_id
            && (int) $user->id !== (int) $stop->trip->driver_id
            && in_array($stop->status, [
                \App\Enums\TripStopStatus::ARRIVED,
                \App\Enums\TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
                \App\Enums\TripStopStatus::HANDED_OVER,
            ], true);
    }
}
