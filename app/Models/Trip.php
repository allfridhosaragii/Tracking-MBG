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
        'loading_verified',
        'loading_verified_at',
        'loading_verified_by',
        'loading_notes',
        'dispatch_approved',
        'dispatch_approved_at',
        'dispatch_approved_by',
        'dispatch_notes',
        'route_geometry',
        'total_distance_m',
        'estimated_duration_s',
        'tracking_status',
        'active_tracking_session_id',
        'tracking_started_at',
        'tracking_ended_at',
        'last_location_at',
        'last_latitude',
        'last_longitude',
        'last_accuracy_m',
        'status',
        'created_by',
        'return_started_at',
        'arrived_at_sppg_at',
        'return_notes',
        'closed_by',
        'total_trays_returned',
        'total_trays_damaged',
        'total_trays_missing',
        'tray_reconciliation_notes',
    ];

    protected $casts = [
        'status' => TripStatus::class,
        'trip_type' => TripType::class,
        'service_date' => 'date',
        'planned_departure_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'return_started_at' => 'datetime',
        'arrived_at_sppg_at' => 'datetime',
        'vehicle_checked_at' => 'datetime',
        'vehicle_check_passed' => 'boolean',
        'loading_verified' => 'boolean',
        'loading_verified_at' => 'datetime',
        'dispatch_approved' => 'boolean',
        'dispatch_approved_at' => 'datetime',
        'total_distance_m' => 'integer',
        'estimated_duration_s' => 'integer',
        'tracking_started_at' => 'datetime',
        'tracking_ended_at' => 'datetime',
        'last_location_at' => 'datetime',
        'last_latitude' => 'float',
        'last_longitude' => 'float',
        'last_accuracy_m' => 'float',
        'total_trays_returned' => 'integer',
        'total_trays_damaged' => 'integer',
        'total_trays_missing' => 'integer',
    ];

    public function sppg(): BelongsTo
    {
        return $this->belongsTo(Sppg::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function loadingVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'loading_verified_by');
    }

    public function dispatchApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatch_approved_by');
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

    public function incidents(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Incident::class, 'subject');
    }

    public function gpsLocations(): HasMany
    {
        return $this->hasMany(GpsLocation::class);
    }

    public function latestGpsLocation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(GpsLocation::class)->latestOfMany('recorded_at');
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
        return (int) $this->deliveryItems()->sum('delivery_items.loaded_portions');
    }

    public function getTotalPlannedPortionsAttribute(): int
    {
        return (int) $this->deliveryItems()->sum('delivery_items.planned_portions');
    }

    public function isReturning(): bool
    {
        return $this->return_started_at !== null && $this->arrived_at_sppg_at === null;
    }

    public function hasArrivedAtDepot(): bool
    {
        return $this->arrived_at_sppg_at !== null;
    }

    public function isCompleted(): bool
    {
        return $this->status === TripStatus::COMPLETED;
    }
}
