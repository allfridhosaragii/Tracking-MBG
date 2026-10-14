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
     * Determine whether the user can finalize trip closure (Gate 5 & Gate F).
     * Assigned Kurir (only if no open incidents exist) or Kepala SPPG of the tenant.
     * Enforces separation of duties if Kepala SPPG was also the assigned driver with open incidents.
     */
    public function closeTrip(User $user, Trip $trip): bool
    {
        if (! $user->is_active || (int) $user->sppg_id !== (int) $trip->sppg_id) {
            return false;
        }

        // Check if trip, its stops, or its tray returns have any active unresolved incidents
        $stopIds = $trip->stops()->pluck('id');
        $serviceRecordIds = \App\Models\SchoolServiceRecord::whereIn('trip_stop_id', $stopIds)->pluck('id');
        $trayReturnIds = \App\Models\TrayReturn::whereIn('school_service_record_id', $serviceRecordIds)->pluck('id');

        $hasUnresolvedIncidents = \App\Models\Incident::where(function ($q) use ($trip, $stopIds, $serviceRecordIds, $trayReturnIds) {
            $q->where(function ($q2) use ($trip) {
                $q2->where('subject_type', Trip::class)
                   ->where('subject_id', $trip->id);
            })->orWhere(function ($q2) use ($stopIds) {
                $q2->where('subject_type', \App\Models\TripStop::class)
                   ->whereIn('subject_id', $stopIds);
            })->orWhere(function ($q2) use ($serviceRecordIds) {
                $q2->where('subject_type', \App\Models\SchoolServiceRecord::class)
                   ->whereIn('subject_id', $serviceRecordIds);
            })->orWhere(function ($q2) use ($trayReturnIds) {
                $q2->where('subject_type', \App\Models\TrayReturn::class)
                   ->whereIn('subject_id', $trayReturnIds);
            });
        })->whereNotIn('status', [\App\Enums\IncidentStatus::RESOLVED, \App\Enums\IncidentStatus::CLOSED])->exists();

        if ($user->role === UserRole::KURIR) {
            // Kurir can close their assigned trip only if there are NO unresolved incidents.
            // If an incident is unresolved (OPEN, ACKNOWLEDGED, IN_PROGRESS, REOPENED), closure must be approved by Kepala SPPG.
            if ($hasUnresolvedIncidents) {
                return false;
            }

            return (int) $user->id === (int) $trip->driver_id;
        }

        if ($user->role === UserRole::KEPALA_SPPG) {
            // Separation of duties: If Kepala SPPG was also the driver on this trip,
            // they cannot self-approve closure when there are unresolved incidents.
            if ($hasUnresolvedIncidents && (int) $trip->driver_id === (int) $user->id) {
                return false;
            }

            return true;
        }

        return false;
    }
}
