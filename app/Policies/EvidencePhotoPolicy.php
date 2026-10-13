<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\EvidencePhoto;
use App\Models\FoodBatch;
use App\Models\User;

class EvidencePhotoPolicy
{
    /**
     * Determine whether the user can view or download the evidence photo.
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
                return (int) $user->sppg_id === $sppgId;
            }

            // HIGH GATE E: PIC Sekolah can ONLY view if their specific school receives an allocation from this specific batch
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

        // Default: only same SPPG active staff
        return (int) $user->sppg_id === (int) $photo->uploadedBy?->sppg_id;
    }
}
