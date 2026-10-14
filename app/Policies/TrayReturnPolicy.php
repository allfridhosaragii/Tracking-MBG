<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\TrayReturn;
use App\Models\User;

class TrayReturnPolicy
{
    /**
     * Determine whether the user can view the tray return record.
     */
    public function view(User $user, TrayReturn $trayReturn): bool
    {
        if (!$user->is_active) {
            return false;
        }

        $sppgId = $trayReturn->sppg?->id;
        $schoolId = $trayReturn->school?->id;

        if ($user->role === UserRole::PIC_SEKOLAH) {
            return (int) $user->school_id === (int) $schoolId;
        }

        // Internal SPPG roles (Kepala, Asisten, Kurir, Pengawas Gizi) must belong to the same SPPG
        return (int) $user->sppg_id === (int) $sppgId;
    }

    /**
     * Determine whether the user can mark containers as ready for pickup (Page P06).
     * Strictly the PIC of the school where the trays are located.
     */
    public function markReady(User $user, TrayReturn $trayReturn): bool
    {
        return $user->is_active
            && $user->role === UserRole::PIC_SEKOLAH
            && (int) $user->school_id === (int) $trayReturn->school?->id;
    }

    /**
     * Determine whether the user can assign a courier or trip stop for tray pickup.
     * Strictly Kepala SPPG of the tenant.
     */
    public function assignPickup(User $user, TrayReturn $trayReturn): bool
    {
        return $user->is_active
            && $user->role === UserRole::KEPALA_SPPG
            && (int) $user->sppg_id === (int) $trayReturn->sppg?->id;
    }

    /**
     * Determine whether the user can record physical container pickup at the school (Page D05).
     * Courier of the same SPPG, and if assigned to a pickup trip, must be the assigned driver.
     */
    public function pickup(User $user, TrayReturn $trayReturn): bool
    {
        if (!$user->is_active || $user->role !== UserRole::KURIR) {
            return false;
        }

        if ((int) $user->sppg_id !== (int) $trayReturn->sppg?->id) {
            return false;
        }

        // If specific pickup trip stop is assigned, driver must match
        if ($trayReturn->pickup_trip_stop_id && $trayReturn->pickupTripStop?->trip) {
            return (int) $trayReturn->pickupTripStop->trip->driver_id === (int) $user->id;
        }

        return true;
    }

    /**
     * Determine whether the user can receive and reconcile returning trays at SPPG depot (Page A03).
     * Asisten Lapangan or Kepala SPPG of the tenant.
     */
    public function receive(User $user, TrayReturn $trayReturn): bool
    {
        return $user->is_active
            && in_array($user->role, [UserRole::ASISTEN_LAPANGAN, UserRole::KEPALA_SPPG], true)
            && (int) $user->sppg_id === (int) $trayReturn->sppg?->id;
    }
}
