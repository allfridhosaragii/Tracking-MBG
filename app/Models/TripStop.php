<?php

namespace App\Models;

use App\Enums\TripStopStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TripStop extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'school_id',
        'stop_type',
        'sequence_number',
        'planned_arrival_at',
        'estimated_arrival_at',
        'eta_updated_at',
        'remaining_distance_m',
        'arrived_at',
        'handed_over_at',
        'pre_arrival_notified_at',
        'status',
    ];

    protected $casts = [
        'status' => TripStopStatus::class,
        'planned_arrival_at' => 'datetime',
        'estimated_arrival_at' => 'datetime',
        'eta_updated_at' => 'datetime',
        'arrived_at' => 'datetime',
        'handed_over_at' => 'datetime',
        'pre_arrival_notified_at' => 'datetime',
        'sequence_number' => 'integer',
        'remaining_distance_m' => 'integer',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function deliveryItems(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    public function getTotalLoadedPortionsAttribute(): int
    {
        return (int) $this->deliveryItems->sum('loaded_portions');
    }

    public function getTotalReleasedPortionsAttribute(): int
    {
        return (int) $this->deliveryItems->sum(function ($item) {
            return $item->released_portions ?? $item->planned_portions;
        });
    }
}
