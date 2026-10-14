<?php

namespace App\Models;

use App\Enums\SchoolReceiptStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SchoolServiceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_stop_id',
        'received_by',
        'received_at',
        'expected_portions',
        'received_portions',
        'damaged_portions',
        'shortage_portions',
        'distribution_started_at',
        'served_portions',
        'leftover_portions',
        'meal_completed_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => SchoolReceiptStatus::class,
        'received_at' => 'datetime',
        'distribution_started_at' => 'datetime',
        'meal_completed_at' => 'datetime',
        'expected_portions' => 'integer',
        'received_portions' => 'integer',
        'damaged_portions' => 'integer',
        'shortage_portions' => 'integer',
        'served_portions' => 'integer',
        'leftover_portions' => 'integer',
    ];

    public function tripStop(): BelongsTo
    {
        return $this->belongsTo(TripStop::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function qualityChecks(): HasMany
    {
        return $this->hasMany(QualityCheck::class, 'trip_stop_id', 'trip_stop_id');
    }

    public function evidencePhotos(): MorphMany
    {
        return $this->morphMany(EvidencePhoto::class, 'evidenceable');
    }

    public function trayReturn(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TrayReturn::class);
    }

    public function incidents(): MorphMany
    {
        return $this->morphMany(Incident::class, 'subject');
    }

    /**
     * Check if the service record has reached an officially confirmed state.
     * All states other than WAITING represent confirmed decisions.
     */
    public function isConfirmed(): bool
    {
        return $this->status !== null && $this->status !== SchoolReceiptStatus::WAITING;
    }

    public function isHeld(): bool
    {
        return $this->status === SchoolReceiptStatus::HELD;
    }

    public function isRejected(): bool
    {
        return $this->status === SchoolReceiptStatus::REJECTED;
    }

    public function hasDiscrepancy(): bool
    {
        return $this->status === SchoolReceiptStatus::RECEIVED_WITH_DISCREPANCY
            || (int) $this->damaged_portions > 0
            || (int) $this->shortage_portions > 0
            || ($this->received_portions !== null && (int) $this->received_portions > (int) $this->expected_portions);
    }
}

