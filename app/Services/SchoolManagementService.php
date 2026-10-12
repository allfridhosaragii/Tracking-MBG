<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SchoolManagementService
{
    /**
     * Create a new school under the given SPPG and optionally assign a PIC.
     */
    public function createSchool(array $data, int $sppgId): School
    {
        return DB::transaction(function () use ($data, $sppgId) {
            $slug = ! empty($data['public_slug']) 
                ? Str::slug($data['public_slug']) 
                : $this->generateUniqueSlug($data['name']);

            $school = School::create([
                'sppg_id' => $sppgId,
                'school_code' => trim($data['school_code']),
                'name' => trim($data['name']),
                'education_level' => $data['education_level'],
                'address' => trim($data['address']),
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'registered_beneficiaries' => (int) ($data['registered_beneficiaries'] ?? 0),
                'public_slug' => $slug,
                'is_active' => true,
            ]);

            if (! empty($data['pic_user_id'])) {
                $this->assignPicToSchool((int) $data['pic_user_id'], $school->id);
            }

            return $school;
        });
    }

    /**
     * Update an existing school and synchronize PIC assignment.
     */
    public function updateSchool(School $school, array $data): School
    {
        return DB::transaction(function () use ($school, $data) {
            $slug = ! empty($data['public_slug']) 
                ? Str::slug($data['public_slug']) 
                : $school->public_slug;

            $school->update([
                'school_code' => trim($data['school_code']),
                'name' => trim($data['name']),
                'education_level' => $data['education_level'],
                'address' => trim($data['address']),
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'registered_beneficiaries' => (int) ($data['registered_beneficiaries'] ?? 0),
                'public_slug' => $slug,
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : $school->is_active,
            ]);

            $newPicId = ! empty($data['pic_user_id']) ? (int) $data['pic_user_id'] : null;
            $currentPic = $school->picUsers()->first();

            if ($currentPic && $currentPic->id !== $newPicId) {
                // Lepaskan PIC lama
                $currentPic->update(['school_id' => null]);
            }

            if ($newPicId) {
                $this->assignPicToSchool($newPicId, $school->id);
            }

            return $school->fresh(['sppg', 'picUsers']);
        });
    }

    /**
     * Toggle school active status (nonaktifkan tanpa hard-delete historis).
     */
    public function toggleSchoolActive(School $school): School
    {
        return DB::transaction(function () use ($school) {
            $school->is_active = ! $school->is_active;
            $school->save();

            return $school;
        });
    }

    /**
     * Assign a user as PIC of a school.
     */
    protected function assignPicToSchool(int $userId, int $schoolId): void
    {
        $user = User::findOrFail($userId);
        $user->update([
            'school_id' => $schoolId,
            'role' => UserRole::PIC_SEKOLAH,
        ]);
    }

    /**
     * Generate a unique public slug for the school.
     */
    protected function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (School::where('public_slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Get candidate users eligible for PIC Sekolah assignment.
     */
    public function getAvailablePicCandidates(?int $currentSchoolId = null): Collection
    {
        return User::where('is_active', true)
            ->where(function ($query) use ($currentSchoolId) {
                $query->whereNull('school_id')
                    ->orWhere('role', UserRole::PIC_SEKOLAH)
                    ->when($currentSchoolId, fn($q) => $q->orWhere('school_id', $currentSchoolId));
            })
            ->where('role', '!=', UserRole::KEPALA_SPPG)
            ->orderBy('name')
            ->get();
    }
}
