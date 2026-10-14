<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Trip;
use App\Models\User;

class TripPolicy
{
    /**
     * Determine whether the user can view the list of trips.
     */
    public function viewAny(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return in_array($user->role, [
            UserRole::KEPALA_SPPG,
            UserRole::ASISTEN_LAPANGAN,
            UserRole::KURIR,
        ], true);
    }

    /**
     * Determine whether the user can view a specific trip.
     * Enforces tenant and role scoping.
     */
    public function view(User $user, Trip $trip): bool
    {
        if (! $user->is_active) {
            return false;
        }

        // Kepala SPPG & Asisten Lapangan can view trips in their SPPG
        if (in_array($user->role, [UserRole::KEPALA_SPPG, UserRole::ASISTEN_LAPANGAN], true)) {
            return (int) $user->sppg_id === (int) $trip->sppg_id;
        }

        // Kurir can only view their own assigned trip
        if ($user->role === UserRole::KURIR) {
            return (int) $user->id === (int) $trip->driver_id;
        }

        // PIC Sekolah can view only if their school is a destination stop on this trip
        if ($user->role === UserRole::PIC_SEKOLAH) {
            if (! $user->school_id) {
                return false;
            }
            return $trip->stops()->where('school_id', $user->school_id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can plan and create a new trip.
     * Only active Kepala SPPG of the tenant.
     */
    public function create(User $user): bool
    {
        return $user->is_active && $user->role === UserRole::KEPALA_SPPG;
    }

    /**
     * Determine whether the user can update/reassign a trip.
     */
    public function update(User $user, Trip $trip): bool
    {
        return $user->is_active
            && $user->role === UserRole::KEPALA_SPPG
            && (int) $user->sppg_id === (int) $trip->sppg_id;
    }

    /**
     * Determine whether the user can perform the pre-trip vehicle check.
     * Strictly assigned driver only.
     */
    public function checkVehicle(User $user, Trip $trip): bool
    {
        return $user->is_active
            && $user->role === UserRole::KURIR
            && (int) $user->id === (int) $trip->driver_id;
    }

    /**
     * Determine whether the user can start dispatching the trip.
     * Strictly assigned driver only.
     */
    public function start(User $user, Trip $trip): bool
    {
        return $user->is_active
            && $user->role === UserRole::KURIR
            && (int) $user->id === (int) $trip->driver_id;
    }

    /**
     * Determine whether the user can verify physical loading for a trip.
     * Asisten Lapangan or Kepala SPPG in the same SPPG.
     */
    public function verifyLoading(User $user, Trip $trip): bool
    {
        return $user->is_active
            && in_array($user->role, [UserRole::ASISTEN_LAPANGAN, UserRole::KEPALA_SPPG], true)
            && (int) $user->sppg_id === (int) $trip->sppg_id;
    }

    /**
     * Determine whether the user can approve dispatch for a trip.
     * Kepala SPPG in the same SPPG, preventing self-approval if also assigned as driver.
     */
    public function approveDispatch(User $user, Trip $trip): bool
    {
        return $user->is_active
            && $user->role === UserRole::KEPALA_SPPG
            && (int) $user->sppg_id === (int) $trip->sppg_id
            && (int) $trip->driver_id !== (int) $user->id;
    }

    /**
     * Determine whether the user can record GPS telemetry for a trip.
     * Strictly assigned driver only, in the same SPPG.
     */
    public function recordTelemetry(User $user, Trip $trip): bool
    {
        return $user->is_active
            && $user->role === UserRole::KURIR
            && (int) $user->id === (int) $trip->driver_id
            && (int) $user->sppg_id === (int) $trip->sppg_id;
    }

    /**
     * Determine whether the user can cancel a trip.
     * Kepala SPPG only.
     */
    public function cancel(User $user, Trip $trip): bool
    {
        return $user->is_active
            && $user->role === UserRole::KEPALA_SPPG
            && (int) $user->sppg_id === (int) $trip->sppg_id;
    }

    /**
     * Determine whether the user can trigger the return journey towards SPPG depot (Gate 3).
     * Assigned Kurir or Kepala SPPG of the tenant.
     */
    public function returnToDepot(User $user, Trip $trip): bool
    {
        if (! $user->is_active || (int) $user->sppg_id !== (int) $trip->sppg_id) {
            return false;
        }

        if ($user->role === UserRole::KURIR) {
            return (int) $user->id === (int) $trip->driver_id;
        }

        return $user->role === UserRole::KEPALA_SPPG;
    }

    /**
     * Determine whether the user can record arrival of the fleet at SPPG depot (Gate 3).
     * Assigned Kurir, Asisten Lapangan, or Kepala SPPG in the same SPPG.
     */
    public function arriveDepot(User $user, Trip $trip): bool
    {
        if (! $user->is_active || (int) $user->sppg_id !== (int) $trip->sppg_id) {
            return false;
        }

        if ($user->role === UserRole::KURIR) {
            return (int) $user->id === (int) $trip->driver_id;
        }

        return in_array($user->role, [UserRole::ASISTEN_LAPANGAN, UserRole::KEPALA_SPPG], true);
    }

    /**
     * Determine whether the user can reconcile returned trays / ompreng (Gate 4).
     * Asisten Lapangan, assigned Kurir, or Kepala SPPG in the same SPPG.
     */
    public function reconcileTrays(User $user, Trip $trip): bool
    {
        if (! $user->is_active || (int) $user->sppg_id !== (int) $trip->sppg_id) {
            return false;
        }

        if ($user->role === UserRole::KURIR) {
            return (int) $user->id === (int) $trip->driver_id;
        }

        return in_array($user->role, [UserRole::ASISTEN_LAPANGAN, UserRole::KEPALA_SPPG], true);
    }

    /**
     * Determine whether the user can finalize trip closure (Gate 5).
     * Assigned Kurir or Kepala SPPG of the tenant.
     */
    public function closeTrip(User $user, Trip $trip): bool
    {
        if (! $user->is_active || (int) $user->sppg_id !== (int) $trip->sppg_id) {
            return false;
        }

        if ($user->role === UserRole::KURIR) {
            return (int) $user->id === (int) $trip->driver_id;
        }

        return $user->role === UserRole::KEPALA_SPPG;
    }
}
