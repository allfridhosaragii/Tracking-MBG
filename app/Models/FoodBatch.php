<?php

namespace App\Models;

use App\Enums\FoodBatchStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class FoodBatch extends Model
{
    protected $fillable = [
        'production_plan_id',
        'menu_id',
        'batch_code',
        'portion_category',
        'planned_portions',
        'actual_portions',
        'accepted_portions',
        'status',
        'released_by',
        'released_at',
        'created_by',
        'notes',
        'has_evidence_exception',
        'evidence_exception_reason',
        'evidence_exception_requested_by',
        'evidence_exception_status',
        'evidence_exception_approved_by',
    ];

    protected $casts = [
        'planned_portions' => 'integer',
        'actual_portions' => 'integer',
        'accepted_portions' => 'integer',
        'status' => FoodBatchStatus::class,
        'released_at' => 'datetime',
        'has_evidence_exception' => 'boolean',
    ];

    public function productionPlan(): BelongsTo
    {
        return $this->belongsTo(ProductionPlan::class);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function recipeMenu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function stageLogs(): HasMany
    {
        return $this->hasMany(ProductionStageLog::class);
    }

    public function qualityChecks(): HasMany
    {
        return $this->hasMany(QualityCheck::class);
    }

    public function evidencePhotos(): MorphMany
    {
        return $this->morphMany(EvidencePhoto::class, 'evidenceable');
    }

    public function deliveryItems(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function releaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function exceptionApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evidence_exception_approved_by');
    }

    public function exceptionRequester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evidence_exception_requested_by');
    }

    public function getSppgIdAttribute(): ?int
    {
        return $this->productionPlan?->sppg_id;
    }

    public function scopeForSppg(Builder $query, int $sppgId): Builder
    {
        return $query->whereHas('productionPlan', function ($q) use ($sppgId) {
            $q->where('sppg_id', $sppgId);
        });
    }

    public function scopeReadyForQc(Builder $query): Builder
    {
        return $query->where('status', FoodBatchStatus::QC_CHECK);
    }

    public function scopeReleased(Builder $query): Builder
    {
        return $query->where('status', FoodBatchStatus::RELEASED);
    }

    public function currentStageLog(): ?ProductionStageLog
    {
        return ProductionStageLog::where('food_batch_id', $this->id)->latest('id')->first();
    }

    public function latestQualityCheck(): ?QualityCheck
    {
        return QualityCheck::where('food_batch_id', $this->id)->latest('id')->first();
    }

    public function isReleased(): bool
    {
        return $this->status === FoodBatchStatus::RELEASED;
    }

    public function isOnHold(): bool
    {
        return $this->status === FoodBatchStatus::ON_HOLD;
    }

    public function isReadyForQc(): bool
    {
        return $this->status === FoodBatchStatus::QC_CHECK;
    }

    public function discrepancy(): ?int
    {
        if ($this->actual_portions === null) {
            return null;
        }

        return (int) $this->actual_portions - (int) $this->planned_portions;
    }

    public function hasDiscrepancy(): bool
    {
        return $this->actual_portions !== null && (int) $this->actual_portions !== (int) $this->planned_portions;
    }

    public function hasFoodReadyPhoto(): bool
    {
        return $this->evidencePhotos()
            ->where('evidence_type', 'FOOD_READY')
            ->exists();
    }

    public function foodReadyPhoto(): ?EvidencePhoto
    {
        return $this->evidencePhotos()
            ->where('evidence_type', 'FOOD_READY')
            ->latest('id')
            ->first();
    }

    public function hasValidEvidence(): bool
    {
        return $this->hasFoodReadyPhoto() || (
            $this->has_evidence_exception
            && $this->evidence_exception_status === 'APPROVED'
            && $this->evidence_exception_approved_by !== null
        );
    }

    public function acceptedDiscrepancy(): ?int
    {
        if ($this->accepted_portions === null || $this->actual_portions === null) {
            return null;
        }

        return (int) $this->accepted_portions - (int) $this->actual_portions;
    }
}
