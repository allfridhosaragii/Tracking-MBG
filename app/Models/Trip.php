<?php

namespace App\Models;

use App\Enums\TripStatus;
use App\Enums\TripType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'sppg_id',
        'driver_id',
        'vehicle_id',
        'trip_code',
        'trip_type',
        'service_date',
        'planned_departure_at',
        'started_at',
        'completed_at',
        'vehicle_check_passed',
        'vehicle_checked_at',
        'vehicle_check_notes',
        'tracking_status',
        'tracking_started_at',
        'tracking_ended_at',
        'last_location_at',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => TripStatus::class,
        'trip_type' => TripType::class,
        'service_date' => 'date',
        'planned_departure_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'vehicle_checked_at' => 'datetime',
        'vehicle_check_passed' => 'boolean',
        'tracking_started_at' => 'datetime',
        'tracking_ended_at' => 'datetime',
        'last_location_at' => 'datetime',
    ];

    public function sppg(): BelongsTo
    {
        return $this->belongsTo(Sppg::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(TripStop::class)->orderBy('sequence_number');
    }

    public function deliveryItems(): HasManyThrough
    {
        return $this->hasManyThrough(DeliveryItem::class, TripStop::class);
    }

    public function scopeForSppg(Builder $query, int $sppgId): Builder
    {
        return $query->where('sppg_id', $sppgId);
    }

    public function scopeForDriver(Builder $query, int $driverId): Builder
    {
        return $query->where('driver_id', $driverId);
    }

    public function getTotalLoadedPortionsAttribute(): int
    {
        return (int) $this->deliveryItems()->sum('loaded_portions');
    }

    public function getTotalPlannedPortionsAttribute(): int
    {
        return (int) $this->deliveryItems()->sum('planned_portions');
    }
}
