<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Menu;
use App\Models\User;

class MenuPolicy
{
    /**
     * Determine whether the user can view any menus.
     */
    public function viewAny(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->hasRole(UserRole::PENGAWAS_GIZI) || $user->hasRole(UserRole::KEPALA_SPPG);
    }

    /**
     * Determine whether the user can view the menu.
     */
    public function view(User $user, Menu $menu): bool
    {
        if (! $user->is_active) {
            return false;
        }

        // Must belong to the same SPPG tenant
        if ((int) $user->getEffectiveSppgId() !== (int) $menu->sppg_id) {
            return false;
        }

        // PENGAWAS_GIZI, KEPALA_SPPG, and ASISTEN_LAPANGAN have view permissions
        return $user->hasRole(UserRole::PENGAWAS_GIZI)
            || $user->hasRole(UserRole::KEPALA_SPPG)
            || $user->hasRole(UserRole::ASISTEN_LAPANGAN);
    }

    /**
     * Determine whether the user can create menus.
     */
    public function create(User $user): bool
    {
        return $user->is_active && $user->hasRole(UserRole::PENGAWAS_GIZI);
    }

    /**
     * Determine whether the user can update the menu.
     * Active menus are immutable! Only DRAFT can be updated.
     */
    public function update(User $user, Menu $menu): bool
    {
        return $user->is_active
            && $user->hasRole(UserRole::PENGAWAS_GIZI)
            && (int) $user->sppg_id === (int) $menu->sppg_id
            && $menu->isDraft();
    }

    /**
     * Determine whether the user can delete the draft menu.
     */
    public function delete(User $user, Menu $menu): bool
    {
        return $user->is_active
            && $user->hasRole(UserRole::PENGAWAS_GIZI)
            && (int) $user->sppg_id === (int) $menu->sppg_id
            && $menu->isDraft();
    }

    /**
     * Determine whether the user can activate the menu.
     */
    public function activate(User $user, Menu $menu): bool
    {
        return $user->is_active
            && $user->hasRole(UserRole::PENGAWAS_GIZI)
            && (int) $user->sppg_id === (int) $menu->sppg_id
            && $menu->isDraft();
    }

    /**
     * Determine whether the user can branch a new version.
     */
    public function createNewVersion(User $user, Menu $menu): bool
    {
        return $user->is_active
            && $user->hasRole(UserRole::PENGAWAS_GIZI)
            && (int) $user->sppg_id === (int) $menu->sppg_id;
    }
}
