<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Incident;
use App\Models\User;

class IncidentPolicy
{
    /**
     * Determine whether the user can view the incident.
     */
    public function view(User $user, Incident $incident): bool
    {
        if (!$user->is_active) {
            return false;
        }

        // PIC can view incidents pertaining to their assigned school
        if ($user->role === UserRole::PIC_SEKOLAH) {
            return (int) $user->school_id === (int) $incident->school_id
                || (int) $user->id === (int) $incident->reported_by;
        }

        // Internal SPPG users must belong to the same SPPG
        return (int) $user->sppg_id === (int) $incident->sppg_id;
    }

    /**
     * Determine whether the user can report an incident.
     */
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Determine whether the user can acknowledge an incident report.
     * Kepala SPPG or Pengawas Gizi of the tenant.
     */
    public function acknowledge(User $user, Incident $incident): bool
    {
        return $user->is_active
            && in_array($user->role, [UserRole::KEPALA_SPPG, UserRole::PENGAWAS_GIZI], true)
            && (int) $user->sppg_id === (int) $incident->sppg_id;
    }

    /**
     * Determine whether the user can start active progress / investigation on an incident.
     */
    public function startProgress(User $user, Incident $incident): bool
    {
        return $user->is_active
            && in_array($user->role, [UserRole::KEPALA_SPPG, UserRole::PENGAWAS_GIZI, UserRole::ASISTEN_LAPANGAN], true)
            && (int) $user->sppg_id === (int) $incident->sppg_id;
    }

    /**
     * Determine whether the user can resolve an incident with corrective findings.
     * Kepala SPPG or Pengawas Gizi of the tenant.
     */
    public function resolve(User $user, Incident $incident): bool
    {
        return $user->is_active
            && in_array($user->role, [UserRole::KEPALA_SPPG, UserRole::PENGAWAS_GIZI], true)
            && (int) $user->sppg_id === (int) $incident->sppg_id;
    }

    /**
     * Determine whether the user can formally close an incident.
     * Strictly Kepala SPPG of the tenant.
     */
    public function close(User $user, Incident $incident): bool
    {
        return $user->is_active
            && $user->role === UserRole::KEPALA_SPPG
            && (int) $user->sppg_id === (int) $incident->sppg_id;
    }

    /**
     * Determine whether the user can reopen an incident.
     * Original reporter or Kepala SPPG of the tenant.
     */
    public function reopen(User $user, Incident $incident): bool
    {
        if (!$user->is_active) {
            return false;
        }

        if ((int) $user->id === (int) $incident->reported_by) {
            return true;
        }

        return $user->role === UserRole::KEPALA_SPPG
            && (int) $user->sppg_id === (int) $incident->sppg_id;
    }
}
