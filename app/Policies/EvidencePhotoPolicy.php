<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\EvidencePhoto;
use App\Models\FoodBatch;
use App\Models\Incident;
use App\Models\SchoolServiceRecord;
use App\Models\TrayReturn;
use App\Models\TripStop;
use App\Models\User;

class EvidencePhotoPolicy
{
    /**
     * Determine whether the user can view or download the evidence photo.
     * Enforces strict separation of duties, tenant isolation, and private access.
     */
    public function view(User $user, EvidencePhoto $photo): bool
    {
        if (! $user->is_active) {
            return false;
        }

        // 1. If photo belongs to a FoodBatch
        if ($photo->evidenceable_type === FoodBatch::class) {
            $batch = $photo->evidenceable ?? FoodBatch::find($photo->evidenceable_id);
            if (! $batch || ! $batch->productionPlan) {
                return false;
            }

            $sppgId = (int) $batch->productionPlan->sppg_id;

            // Internal staff of the same SPPG
            if (in_array($user->role, [
                UserRole::KEPALA_SPPG,
                UserRole::PENGAWAS_GIZI,
                UserRole::ASISTEN_LAPANGAN,
            ], true)) {
                return $user->sppg_id !== null && (int) $user->sppg_id === $sppgId;
            }

            // PIC Sekolah can ONLY view if their specific school receives an allocation from this specific batch
            if ($user->role === UserRole::PIC_SEKOLAH && $user->school_id) {
                return $batch->deliveryItems()
                    ->whereHas('productionAllocation', function ($q) use ($user) {
                        $q->where('school_id', $user->school_id);
                    })
                    ->exists();
            }

            // Driver only if assigned to a trip carrying a delivery item from this batch
            if ($user->role === UserRole::KURIR) {
                return $batch->deliveryItems()
                    ->whereHas('tripStop.trip', function ($q) use ($user) {
                        $q->where('driver_id', $user->id);
                    })
                    ->exists();
            }

            return false;
        }

        // 2. If photo belongs to a TripStop (e.g. SCHOOL_HANDOVER)
        if ($photo->evidenceable_type === TripStop::class) {
            $stop = $photo->evidenceable ?? TripStop::with('trip')->find($photo->evidenceable_id);
            if (! $stop || ! $stop->trip) {
                return false;
            }

            // PIC Sekolah can ONLY view photos belonging to their assigned school
            if ($user->role === UserRole::PIC_SEKOLAH) {
                return $user->school_id !== null && (int) $user->school_id === (int) $stop->school_id;
            }

            // Courier can ONLY view photos of trips they are personally assigned to
            if ($user->role === UserRole::KURIR) {
                return (int) $user->id === (int) $stop->trip->driver_id;
            }

            // Internal SPPG staff within the same SPPG scope
            if (in_array($user->role, [
                UserRole::KEPALA_SPPG,
                UserRole::PENGAWAS_GIZI,
                UserRole::ASISTEN_LAPANGAN,
            ], true)) {
                return $user->sppg_id !== null && (int) $user->sppg_id === (int) $stop->trip->sppg_id;
            }

            return false;
        }

        // 3. If photo belongs to a SchoolServiceRecord (e.g. RECEIPT_CONFIRMATION / RECEIPT_PROBLEM)
        if ($photo->evidenceable_type === SchoolServiceRecord::class) {
            $record = $photo->evidenceable ?? SchoolServiceRecord::with('tripStop.trip')->find($photo->evidenceable_id);
            $stop = $record?->tripStop;
            if (! $record || ! $stop || ! $stop->trip) {
                return false;
            }

            // PIC Sekolah can ONLY view photos belonging to their assigned school
            if ($user->role === UserRole::PIC_SEKOLAH) {
                return $user->school_id !== null && (int) $user->school_id === (int) $stop->school_id;
            }

            // Courier can ONLY view photos of trips they are personally assigned to
            if ($user->role === UserRole::KURIR) {
                return (int) $user->id === (int) $stop->trip->driver_id;
            }

            // Internal SPPG staff within the same SPPG scope
            if (in_array($user->role, [
                UserRole::KEPALA_SPPG,
                UserRole::PENGAWAS_GIZI,
                UserRole::ASISTEN_LAPANGAN,
            ], true)) {
                return $user->sppg_id !== null && (int) $user->sppg_id === (int) $stop->trip->sppg_id;
            }

            return false;
        }

        // 4. If photo belongs to a TrayReturn (Reverse logistics evidence)
        if ($photo->evidenceable_type === TrayReturn::class) {
            $trayReturn = $photo->evidenceable ?? TrayReturn::find($photo->evidenceable_id);
            if (! $trayReturn) {
                return false;
            }

            $schoolId = $trayReturn->school_id;
            $sppgId = $trayReturn->sppg_id;

            // PIC Sekolah can view if belonging to their school
            if ($user->role === UserRole::PIC_SEKOLAH) {
                return $user->school_id !== null && (int) $user->school_id === (int) $schoolId;
            }

            // Driver of the same SPPG or assigned to the pickup/delivery
            if ($user->role === UserRole::KURIR) {
                return $user->sppg_id !== null && (int) $user->sppg_id === (int) $sppgId;
            }

            // Internal SPPG staff within same SPPG scope
            if (in_array($user->role, [
                UserRole::KEPALA_SPPG,
                UserRole::PENGAWAS_GIZI,
                UserRole::ASISTEN_LAPANGAN,
            ], true)) {
                return $user->sppg_id !== null && (int) $user->sppg_id === (int) $sppgId;
            }

            return false;
        }

        // 5. If photo belongs to an Incident
        if ($photo->evidenceable_type === Incident::class) {
            $incident = $photo->evidenceable ?? Incident::find($photo->evidenceable_id);
            if (! $incident) {
                return false;
            }

            // Reporter can always view
            if ((int) $user->id === (int) $incident->reported_by) {
                return true;
            }

            // PIC Sekolah can view if incident is scoped to their school
            if ($user->role === UserRole::PIC_SEKOLAH) {
                return $user->school_id !== null && (int) $user->school_id === (int) $incident->school_id;
            }

            // Internal SPPG staff within same SPPG scope
            if (in_array($user->role, [
                UserRole::KEPALA_SPPG,
                UserRole::PENGAWAS_GIZI,
                UserRole::ASISTEN_LAPANGAN,
                UserRole::KURIR,
            ], true)) {
                return $user->sppg_id !== null && (int) $user->sppg_id === (int) $incident->sppg_id;
            }

            return false;
        }

        // Strict default deny for unknown polymorphic types
        return false;
    }
}

