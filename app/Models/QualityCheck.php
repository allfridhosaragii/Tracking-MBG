<?php

namespace App\Models;

use App\Enums\QualityCheckConclusion;
use App\Enums\QualityCheckStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityCheck extends Model
{
    protected $fillable = [
        'food_batch_id',
        'trip_stop_id',
        'check_stage',
        'checked_by',
        'appearance_ok',
        'aroma_ok',
        'texture_ok',
        'taste_ok',
        'taste_tested',
        'taste_skipped_reason',
        'temperature_c',
        'accepted_portions',
        'rejected_portions',
        'sop_reference',
        'decision_reason',
        'conclusion',
        'notes',
        'checked_at',
    ];

    protected $casts = [
        'appearance_ok' => 'boolean',
        'aroma_ok' => 'boolean',
        'texture_ok' => 'boolean',
        'taste_ok' => 'boolean',
        'taste_tested' => 'boolean',
        'temperature_c' => 'decimal:2',
        'accepted_portions' => 'integer',
        'rejected_portions' => 'integer',
        'conclusion' => QualityCheckConclusion::class,
        'check_stage' => QualityCheckStage::class,
        'checked_at' => 'datetime',
    ];

    public function foodBatch(): BelongsTo
    {
        return $this->belongsTo(FoodBatch::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function isSafe(): bool
    {
        return $this->conclusion === QualityCheckConclusion::SAFE;
    }

    public function isUnsafe(): bool
    {
        return $this->conclusion === QualityCheckConclusion::UNSAFE;
    }

    public function isHold(): bool
    {
        return $this->conclusion === QualityCheckConclusion::HOLD;
    }
}
