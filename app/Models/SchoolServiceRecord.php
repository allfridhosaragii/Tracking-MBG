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
}
