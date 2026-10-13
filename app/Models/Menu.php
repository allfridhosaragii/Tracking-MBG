<?php

namespace App\Models;

use App\Enums\MenuStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'sppg_id',
        'menu_code',
        'name',
        'version',
        'status',
        'image_path',
        'effective_from',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'status' => MenuStatus::class,
            'effective_from' => 'date',
            'approved_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function sppg(): BelongsTo
    {
        return $this->belongsTo(Sppg::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function productionPlans(): HasMany
    {
        return $this->hasMany(ProductionPlan::class);
    }

    public function scopeForSppg(Builder $query, int $sppgId): Builder
    {
        return $query->where('sppg_id', $sppgId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', MenuStatus::ACTIVE);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', MenuStatus::DRAFT);
    }

    public function isDraft(): bool
    {
        return $this->status === MenuStatus::DRAFT;
    }

    public function isActive(): bool
    {
        return $this->status === MenuStatus::ACTIVE;
    }

    public function isArchived(): bool
    {
        return $this->status === MenuStatus::ARCHIVED;
    }

    public function isEditable(): bool
    {
        return $this->isDraft();
    }

    /**
     * Total serving weight across all components in grams.
     */
    public function totalServingWeight(): float
    {
        return (float) $this->items->sum('serving_weight_g');
    }

    /**
     * Check if all components have complete nutritional information.
     */
    public function isComplete(): bool
    {
        if ($this->items->isEmpty()) {
            return false;
        }

        foreach ($this->items as $item) {
            if (! $item->isNutritionComplete()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if any item has null nutrient values.
     */
    public function hasIncompleteNutrients(): bool
    {
        return ! $this->isComplete();
    }

    /**
     * Total energy (kcal).
     * Returns null if any component is missing its energy value.
     */
    public function totalEnergy(): ?float
    {
        if ($this->items->isEmpty()) {
            return null;
        }

        $sum = 0.0;
        foreach ($this->items as $item) {
            if ($item->energy_kcal === null) {
                return null;
            }
            $sum += (float) $item->energy_kcal;
        }

        return round($sum, 2);
    }

    /**
     * Total protein (grams).
     * Returns null if any component is missing its protein value.
     */
    public function totalProtein(): ?float
    {
        if ($this->items->isEmpty()) {
            return null;
        }

        $sum = 0.0;
        foreach ($this->items as $item) {
            if ($item->protein_g === null) {
                return null;
            }
            $sum += (float) $item->protein_g;
        }

        return round($sum, 2);
    }

    /**
     * Total carbohydrates (grams).
     * Returns null if any component is missing its carbohydrate value.
     */
    public function totalCarbohydrates(): ?float
    {
        if ($this->items->isEmpty()) {
            return null;
        }

        $sum = 0.0;
        foreach ($this->items as $item) {
            if ($item->carbohydrate_g === null) {
                return null;
            }
            $sum += (float) $item->carbohydrate_g;
        }

        return round($sum, 2);
    }

    /**
     * Total fat (grams).
     * Returns null if any component is missing its fat value.
     */
    public function totalFat(): ?float
    {
        if ($this->items->isEmpty()) {
            return null;
        }

        $sum = 0.0;
        foreach ($this->items as $item) {
            if ($item->fat_g === null) {
                return null;
            }
            $sum += (float) $item->fat_g;
        }

        return round($sum, 2);
    }

    /**
     * Total fiber (grams).
     * Returns null if any component is missing its fiber value.
     */
    public function totalFiber(): ?float
    {
        if ($this->items->isEmpty()) {
            return null;
        }

        $sum = 0.0;
        foreach ($this->items as $item) {
            if ($item->fiber_g === null) {
                return null;
            }
            $sum += (float) $item->fiber_g;
        }

        return round($sum, 2);
    }

    /**
     * List validation issues for completeness check.
     */
    public function completenessViolations(): array
    {
        $violations = [];

        if ($this->items->isEmpty()) {
            $violations[] = 'Menu belum memiliki komponen makanan atau lauk.';
            return $violations;
        }

        foreach ($this->items as $index => $item) {
            $itemNumber = $index + 1;
            if ($item->serving_weight_g <= 0) {
                $violations[] = "Komponen #{$itemNumber} ({$item->item_name}): Berat saji harus lebih dari 0 gram.";
            }
            if ($item->energy_kcal === null) {
                $violations[] = "Komponen #{$itemNumber} ({$item->item_name}): Nilai energi (kkal) belum diisi.";
            }
            if ($item->protein_g === null) {
                $violations[] = "Komponen #{$itemNumber} ({$item->item_name}): Nilai protein (g) belum diisi.";
            }
            if ($item->carbohydrate_g === null) {
                $violations[] = "Komponen #{$itemNumber} ({$item->item_name}): Nilai karbohidrat (g) belum diisi.";
            }
            if ($item->fat_g === null) {
                $violations[] = "Komponen #{$itemNumber} ({$item->item_name}): Nilai lemak (g) belum diisi.";
            }
            if ($item->fiber_g === null) {
                $violations[] = "Komponen #{$itemNumber} ({$item->item_name}): Nilai serat (g) belum diisi.";
            }
            if (empty(trim((string) $item->nutrition_source))) {
                $violations[] = "Komponen #{$itemNumber} ({$item->item_name}): Sumber rujukan gizi wajib dicantumkan.";
            }
        }

        return $violations;
    }

    /**
     * Fetch sibling versions of this menu code in the same SPPG.
     */
    public function getVersionHistory()
    {
        return static::where('sppg_id', $this->sppg_id)
            ->where('menu_code', $this->menu_code)
            ->orderByDesc('version')
            ->get();
    }
}
