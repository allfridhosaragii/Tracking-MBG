<?php

namespace App\Models;

use App\Enums\IncidentCategory;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Incident extends Model
{
    use HasFactory;

    protected $fillable = [
        'sppg_id',
        'school_id',
        'subject_type',
        'subject_id',
        'reported_by',
        'category',
        'severity',
        'status',
        'description',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'category' => IncidentCategory::class,
        'severity' => IncidentSeverity::class,
        'status' => IncidentStatus::class,
        'resolved_at' => 'datetime',
    ];

    public function sppg(): BelongsTo
    {
        return $this->belongsTo(Sppg::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function evidencePhotos(): MorphMany
    {
        return $this->morphMany(EvidencePhoto::class, 'evidenceable');
    }

    public function isResolved(): bool
    {
        return in_array($this->status, [IncidentStatus::RESOLVED, IncidentStatus::CLOSED], true);
    }

    public function isFoodSafety(): bool
    {
        return $this->category === IncidentCategory::FOOD_SAFETY;
    }
}
