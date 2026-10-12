<?php

namespace App\Services;

use App\Enums\MenuStatus;
use App\Models\ActivityLog;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MenuManagementService
{
    /**
     * Create a new draft menu with version 1.
     */
    public function createDraft(array $data, int $sppgId, User $creator): Menu
    {
        return DB::transaction(function () use ($data, $sppgId, $creator) {
            // Check if menu_code already exists for this SPPG
            $existingCount = Menu::where('sppg_id', $sppgId)
                ->where('menu_code', $data['menu_code'])
                ->count();

            $version = $existingCount > 0 ? $existingCount + 1 : 1;

            $menu = Menu::create([
                'sppg_id' => $sppgId,
                'menu_code' => strtoupper(trim($data['menu_code'])),
                'name' => trim($data['name']),
                'version' => $version,
                'status' => MenuStatus::DRAFT,
                'image_path' => $data['image_path'] ?? null,
                'effective_from' => $data['effective_from'] ?? null,
                'created_by' => $creator->id,
            ]);

            ActivityLog::record(
                event: 'menu.draft_created',
                auditable: $menu,
                actor: $creator,
                newValues: [
                    'menu_code' => $menu->menu_code,
                    'name' => $menu->name,
                    'version' => $menu->version,
                    'status' => $menu->status->value,
                ]
            );

            return $menu;
        });
    }

    /**
     * Update an existing draft menu.
     */
    public function updateDraft(Menu $menu, array $data, User $actor): Menu
    {
        if (! $menu->isDraft()) {
            throw ValidationException::withMessages([
                'menu' => ['Menu yang sudah berstatus ' . $menu->status->label() . ' bersifat immutable dan tidak dapat disunting.'],
            ]);
        }

        return DB::transaction(function () use ($menu, $data, $actor) {
            $oldValues = [
                'name' => $menu->name,
                'effective_from' => $menu->effective_from?->format('Y-m-d'),
                'image_path' => $menu->image_path,
            ];

            $menu->update([
                'name' => trim($data['name'] ?? $menu->name),
                'effective_from' => $data['effective_from'] ?? $menu->effective_from,
                'image_path' => array_key_exists('image_path', $data) ? $data['image_path'] : $menu->image_path,
            ]);

            ActivityLog::record(
                event: 'menu.draft_updated',
                auditable: $menu,
                actor: $actor,
                oldValues: $oldValues,
                newValues: [
                    'name' => $menu->name,
                    'effective_from' => $menu->effective_from?->format('Y-m-d'),
                    'image_path' => $menu->image_path,
                ]
            );

            return $menu;
        });
    }

    /**
     * Add a food component to a draft menu.
     */
    public function addMenuItem(Menu $menu, array $itemData, User $actor): MenuItem
    {
        if (! $menu->isDraft()) {
            throw ValidationException::withMessages([
                'menu' => ['Komponen menu hanya dapat ditambahkan pada menu yang berstatus Draft.'],
            ]);
        }

        if (isset($itemData['serving_weight_g']) && (float) $itemData['serving_weight_g'] <= 0) {
            throw ValidationException::withMessages([
                'serving_weight_g' => ['Berat porsi saji harus lebih besar dari 0 gram.'],
            ]);
        }

        return DB::transaction(function () use ($menu, $itemData, $actor) {
            $item = $menu->items()->create([
                'item_name' => trim($itemData['item_name']),
                'serving_weight_g' => (float) $itemData['serving_weight_g'],
                'energy_kcal' => isset($itemData['energy_kcal']) && $itemData['energy_kcal'] !== '' ? (float) $itemData['energy_kcal'] : null,
                'protein_g' => isset($itemData['protein_g']) && $itemData['protein_g'] !== '' ? (float) $itemData['protein_g'] : null,
                'carbohydrate_g' => isset($itemData['carbohydrate_g']) && $itemData['carbohydrate_g'] !== '' ? (float) $itemData['carbohydrate_g'] : null,
                'fat_g' => isset($itemData['fat_g']) && $itemData['fat_g'] !== '' ? (float) $itemData['fat_g'] : null,
                'fiber_g' => isset($itemData['fiber_g']) && $itemData['fiber_g'] !== '' ? (float) $itemData['fiber_g'] : null,
                'nutrition_source' => isset($itemData['nutrition_source']) ? trim($itemData['nutrition_source']) : null,
                'notes' => isset($itemData['notes']) ? trim($itemData['notes']) : null,
            ]);

            ActivityLog::record(
                event: 'menu_item.added',
                auditable: $menu,
                actor: $actor,
                newValues: [
                    'item_id' => $item->id,
                    'item_name' => $item->item_name,
                    'serving_weight_g' => $item->serving_weight_g,
                    'energy_kcal' => $item->energy_kcal,
                    'nutrition_source' => $item->nutrition_source,
                ]
            );

            return $item;
        });
    }

    /**
     * Delete a food component from a draft menu.
     */
    public function deleteMenuItem(MenuItem $item, User $actor): void
    {
        $menu = $item->menu;

        if (! $menu->isDraft()) {
            throw ValidationException::withMessages([
                'menu' => ['Komponen menu hanya dapat dihapus pada menu yang berstatus Draft.'],
            ]);
        }

        DB::transaction(function () use ($item, $menu, $actor) {
            $itemSnapshot = [
                'item_id' => $item->id,
                'item_name' => $item->item_name,
                'serving_weight_g' => $item->serving_weight_g,
            ];

            $item->delete();

            ActivityLog::record(
                event: 'menu_item.deleted',
                auditable: $menu,
                actor: $actor,
                oldValues: $itemSnapshot
            );
        });
    }

    /**
     * Validate completeness and activate a draft menu.
     * Automatically transitions previously active version of the same menu code to ARCHIVED.
     */
    public function activateMenu(Menu $menu, User $approver): Menu
    {
        // Re-read fresh state with lock
        return DB::transaction(function () use ($menu, $approver) {
            $lockedMenu = Menu::where('id', $menu->id)->lockForUpdate()->firstOrFail();

            if (! $lockedMenu->isDraft()) {
                throw ValidationException::withMessages([
                    'status' => ['Hanya menu dengan status Draft yang dapat diaktifkan.'],
                ]);
            }

            // Completeness check
            $lockedMenu->load('items');
            $violations = $lockedMenu->completenessViolations();
            if (! empty($violations)) {
                throw ValidationException::withMessages([
                    'completeness' => $violations,
                ]);
            }

            // Archive any currently active version of this menu code in the same SPPG
            $currentActive = Menu::where('sppg_id', $lockedMenu->sppg_id)
                ->where('menu_code', $lockedMenu->menu_code)
                ->where('status', MenuStatus::ACTIVE)
                ->lockForUpdate()
                ->first();

            if ($currentActive) {
                $currentActive->update([
                    'status' => MenuStatus::ARCHIVED,
                ]);

                ActivityLog::record(
                    event: 'menu.archived_for_new_active',
                    auditable: $currentActive,
                    actor: $approver,
                    oldValues: ['status' => MenuStatus::ACTIVE->value],
                    newValues: ['status' => MenuStatus::ARCHIVED->value],
                    metadata: ['superseded_by_menu_id' => $lockedMenu->id]
                );
            }

            // Activate the target menu
            $lockedMenu->update([
                'status' => MenuStatus::ACTIVE,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            ActivityLog::record(
                event: 'menu.activated',
                auditable: $lockedMenu,
                actor: $approver,
                oldValues: ['status' => MenuStatus::DRAFT->value],
                newValues: [
                    'status' => MenuStatus::ACTIVE->value,
                    'approved_by' => $approver->id,
                    'approved_at' => $lockedMenu->approved_at?->toIso8601String(),
                ]
            );

            return $lockedMenu;
        });
    }

    /**
     * Branch a new Draft version from an existing menu without altering historical version.
     */
    public function createNewVersion(Menu $sourceMenu, User $actor): Menu
    {
        return DB::transaction(function () use ($sourceMenu, $actor) {
            $sourceMenu->load('items');

            // Find current highest version for this menu code in this SPPG
            $maxVersion = (int) Menu::where('sppg_id', $sourceMenu->sppg_id)
                ->where('menu_code', $sourceMenu->menu_code)
                ->max('version');

            $newVersionNumber = $maxVersion + 1;

            $newMenu = Menu::create([
                'sppg_id' => $sourceMenu->sppg_id,
                'menu_code' => $sourceMenu->menu_code,
                'name' => $sourceMenu->name,
                'version' => $newVersionNumber,
                'status' => MenuStatus::DRAFT,
                'image_path' => $sourceMenu->image_path,
                'effective_from' => $sourceMenu->effective_from,
                'created_by' => $actor->id,
                'approved_by' => null,
                'approved_at' => null,
            ]);

            // Clone all items faithfully
            foreach ($sourceMenu->items as $item) {
                $newMenu->items()->create([
                    'item_name' => $item->item_name,
                    'serving_weight_g' => $item->serving_weight_g,
                    'energy_kcal' => $item->energy_kcal,
                    'protein_g' => $item->protein_g,
                    'carbohydrate_g' => $item->carbohydrate_g,
                    'fat_g' => $item->fat_g,
                    'fiber_g' => $item->fiber_g,
                    'nutrition_source' => $item->nutrition_source,
                    'notes' => $item->notes,
                ]);
            }

            ActivityLog::record(
                event: 'menu.version_branched',
                auditable: $newMenu,
                actor: $actor,
                newValues: [
                    'source_menu_id' => $sourceMenu->id,
                    'source_version' => $sourceMenu->version,
                    'new_version' => $newVersionNumber,
                    'status' => MenuStatus::DRAFT->value,
                ]
            );

            return $newMenu;
        });
    }
}
