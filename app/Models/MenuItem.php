<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'item_name',
        'serving_weight_g',
        'energy_kcal',
        'protein_g',
        'carbohydrate_g',
        'fat_g',
        'fiber_g',
        'nutrition_source',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'serving_weight_g' => 'decimal:2',
            'energy_kcal' => 'decimal:2',
            'protein_g' => 'decimal:2',
            'carbohydrate_g' => 'decimal:2',
            'fat_g' => 'decimal:2',
            'fiber_g' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * Determine if this component has full nutrition data specified.
     */
    public function isNutritionComplete(): bool
    {
        return $this->serving_weight_g > 0
            && $this->energy_kcal !== null
            && $this->protein_g !== null
            && $this->carbohydrate_g !== null
            && $this->fat_g !== null
            && $this->fiber_g !== null
            && ! empty(trim((string) $this->nutrition_source));
    }
}
