<?php

namespace App\Models;

use App\Enums\TrayReturnStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TrayReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_service_record_id',
        'pickup_trip_stop_id',
        'expected_trays',
        'ready_count',
        'ready_at',
        'picked_up_count',
        'picked_up_at',
        'returned_count',
        'returned_at',
        'damaged_count',
        'missing_count',
        'status',
        'discrepancy_reason',
    ];

    protected $casts = [
        'status' => TrayReturnStatus::class,
        'expected_trays' => 'integer',
        'ready_count' => 'integer',
        'picked_up_count' => 'integer',
        'returned_count' => 'integer',
        'damaged_count' => 'integer',
        'missing_count' => 'integer',
        'ready_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function schoolServiceRecord(): BelongsTo
    {
        return $this->belongsTo(SchoolServiceRecord::class);
    }

    public function pickupTripStop(): BelongsTo
    {
        return $this->belongsTo(TripStop::class, 'pickup_trip_stop_id');
    }

    public function evidencePhotos(): MorphMany
    {
        return $this->morphMany(EvidencePhoto::class, 'evidenceable');
    }

    public function hasDiscrepancy(): bool
    {
        return $this->status === TrayReturnStatus::DISCREPANCY
            || ($this->damaged_count !== null && $this->damaged_count > 0)
            || ($this->missing_count !== null && $this->missing_count > 0)
            || ($this->returned_count !== null && $this->returned_count !== $this->expected_trays);
    }
}
