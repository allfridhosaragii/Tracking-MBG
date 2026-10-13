<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'sppg_id',
        'plate_number',
        'vehicle_name',
        'vehicle_type',
        'capacity_portions',
        'capacity_note',
        'is_active',
    ];

    protected $casts = [
        'capacity_portions' => 'integer',
        'is_active' => 'boolean',
    ];

    public function sppg(): BelongsTo
    {
        return $this->belongsTo(Sppg::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForSppg(Builder $query, int $sppgId): Builder
    {
        return $query->where('sppg_id', $sppgId);
    }
}
