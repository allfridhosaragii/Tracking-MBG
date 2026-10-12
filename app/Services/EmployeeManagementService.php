<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EmployeeManagementService
{
    /**
     * Create a new employee under the given SPPG.
     */
    public function createEmployee(array $data, int $sppgId, User $actor, ?Request $request = null): User
    {
        return DB::transaction(function () use ($data, $sppgId, $actor, $request) {
            $roleValue = $data['role'] instanceof UserRole ? $data['role']->value : (string) $data['role'];

            $employee = User::create([
                'sppg_id' => $sppgId,
                'school_id' => null,
                'name' => trim($data['name']),
                'email' => strtolower(trim($data['email'])),
                'phone' => ! empty($data['phone']) ? trim($data['phone']) : null,
                'password' => Hash::make($data['password']),
                'role' => $roleValue,
                'is_active' => true,
            ]);

            ActivityLog::record(
                event: 'created',
                auditable: $employee,
                actor: $actor,
                newValues: [
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'role' => $employee->role instanceof UserRole ? $employee->role->value : $employee->role,
                    'is_active' => true,
                ],
                metadata: [
                    'action' => 'Registrasi pegawai baru SPPG',
                ],
                ipAddress: $request?->ip(),
                userAgent: $request?->userAgent()
            );

            return $employee;
        });
    }

    /**
     * Update employee credentials, contact, and role.
     */
    public function updateEmployee(User $employee, array $data, User $actor, ?Request $request = null): User
    {
        return DB::transaction(function () use ($employee, $data, $actor, $request) {
            $newRole = $data['role'] instanceof UserRole ? $data['role']->value : (string) $data['role'];
            $currentRole = $employee->role instanceof UserRole ? $employee->role->value : (string) $employee->role;

            // Aturan 1: Kepala SPPG tidak boleh mengubah rolenya sendiri menjadi role lain
            if ($employee->id === $actor->id && $newRole !== $currentRole) {
                throw ValidationException::withMessages([
                    'role' => 'Kepala SPPG tidak dapat mengubah rolenya sendiri menjadi role lain melalui menu pegawai.',
                ]);
            }

            // Aturan 2: Tidak boleh mempromosikan pegawai menjadi Kepala SPPG (pencegahan eskalasi wewenang)
            if ($employee->id !== $actor->id && $newRole === UserRole::KEPALA_SPPG->value) {
                throw ValidationException::withMessages([
                    'role' => 'Tidak diizinkan mempromosikan staf operasional menjadi Kepala SPPG melalui antarmuka pegawai.',
                ]);
            }

            // Aturan 3: Kurir yang sedang menjalankan trip aktif tidak boleh diubah rolenya
            if ($currentRole === UserRole::KURIR->value && $newRole !== UserRole::KURIR->value && $employee->hasActiveTrips()) {
                throw ValidationException::withMessages([
                    'role' => 'Kurir sedang dalam penugasan pengiriman aktif. Selesaikan atau alihkan pengiriman sebelum mengubah role.',
                ]);
            }

            $oldValues = [
                'name' => $employee->name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'role' => $currentRole,
                'is_active' => (bool) $employee->is_active,
            ];

            $updatePayload = [
                'name' => trim($data['name']),
                'email' => strtolower(trim($data['email'])),
                'phone' => ! empty($data['phone']) ? trim($data['phone']) : null,
                'role' => $newRole,
            ];

            if (isset($data['is_active'])) {
                $targetActive = (bool) $data['is_active'];
                if ($employee->is_active && ! $targetActive) {
                    if ($employee->isSoleActiveHead()) {
                        throw ValidationException::withMessages([
                            'is_active' => 'Tidak dapat menonaktifkan akun Kepala SPPG karena merupakan satu-satunya kepala aktif tersisa pada SPPG ini.',
                        ]);
                    }
                    if ($employee->hasActiveTrips()) {
                        throw ValidationException::withMessages([
                            'is_active' => 'Kurir sedang menjalankan pengiriman aktif. Selesaikan atau alihkan pengiriman terlebih dahulu sebelum menonaktifkan akun.',
                        ]);
                    }
                }
                $updatePayload['is_active'] = $targetActive;
            }

            if (! empty($data['password'])) {
                $updatePayload['password'] = Hash::make($data['password']);
            }

            $employee->update($updatePayload);
            $employee->refresh();

            $newValues = [
                'name' => $employee->name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'role' => $employee->role instanceof UserRole ? $employee->role->value : $employee->role,
                'is_active' => (bool) $employee->is_active,
            ];

            $roleChanged = $oldValues['role'] !== $newValues['role'];

            ActivityLog::record(
                event: $roleChanged ? 'role_changed' : 'updated',
                auditable: $employee,
                actor: $actor,
                oldValues: $oldValues,
                newValues: $newValues,
                metadata: [
                    'action' => $roleChanged ? 'Perubahan hak akses / role pegawai' : 'Pembaruan data pegawai',
                    'role_changed' => $roleChanged,
                ],
                ipAddress: $request?->ip(),
                userAgent: $request?->userAgent()
            );

            return $employee;
        });
    }

    /**
     * Toggle employee active status safely.
     */
    public function toggleActive(User $employee, User $actor, ?Request $request = null): User
    {
        return DB::transaction(function () use ($employee, $actor, $request) {
            $willDeactivate = (bool) $employee->is_active;

            if ($willDeactivate) {
                if ($employee->isSoleActiveHead()) {
                    throw ValidationException::withMessages([
                        'is_active' => 'Tidak dapat menonaktifkan akun Kepala SPPG karena merupakan satu-satunya kepala aktif tersisa pada SPPG ini.',
                    ]);
                }

                if ($employee->hasActiveTrips()) {
                    throw ValidationException::withMessages([
                        'is_active' => 'Kurir sedang menjalankan pengiriman aktif. Selesaikan atau alihkan pengiriman terlebih dahulu sebelum menonaktifkan akun.',
                    ]);
                }
            }

            $oldStatus = (bool) $employee->is_active;
            $employee->is_active = ! $oldStatus;
            $employee->save();

            ActivityLog::record(
                event: 'status_toggled',
                auditable: $employee,
                actor: $actor,
                oldValues: ['is_active' => $oldStatus],
                newValues: ['is_active' => $employee->is_active],
                metadata: [
                    'action' => $employee->is_active ? 'Aktivasi akun pegawai' : 'Deaktivasi akun pegawai',
                ],
                ipAddress: $request?->ip(),
                userAgent: $request?->userAgent()
            );

            return $employee;
        });
    }

    /**
     * Reset employee password with audit trail.
     */
    public function resetPassword(User $employee, string $newPassword, User $actor, ?Request $request = null): User
    {
        return DB::transaction(function () use ($employee, $newPassword, $actor, $request) {
            $employee->password = Hash::make($newPassword);
            $employee->save();

            ActivityLog::record(
                event: 'password_reset',
                auditable: $employee,
                actor: $actor,
                metadata: [
                    'action' => 'Reset kata sandi pegawai oleh Kepala SPPG',
                ],
                ipAddress: $request?->ip(),
                userAgent: $request?->userAgent()
            );

            return $employee;
        });
    }
}
