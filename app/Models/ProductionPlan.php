<?php

namespace App\Models;

use App\Enums\ProductionPlanStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'sppg_id',
        'menu_id',
        'service_date',
        'planned_start_at',
        'status',
        'notes',
        'finalized_by',
        'finalized_at',
        'created_by',
    ];

    protected $casts = [
        'service_date' => 'date',
        'planned_start_at' => 'datetime',
        'finalized_at' => 'datetime',
        'status' => ProductionPlanStatus::class,
    ];

    public function sppg(): BelongsTo
    {
        return $this->belongsTo(Sppg::class);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ProductionAllocation::class);
    }

    public function foodBatches(): HasMany
    {
        return $this->hasMany(FoodBatch::class);
    }

    public function totalAllocatedPortions(): int
    {
        return (int) $this->allocations()->sum('allocated_portions');
    }

    public function totalActualPortions(): int
    {
        return (int) $this->foodBatches()->sum('actual_portions');
    }

    public function totalPlannedBatchPortions(): int
    {
        return (int) $this->foodBatches()->sum('planned_portions');
    }

    public function totalReleasedPortions(): int
    {
        return (int) $this->foodBatches()->where('status', \App\Enums\FoodBatchStatus::RELEASED)->sum('actual_portions');
    }

    public function isFinalized(): bool
    {
        return $this->status === ProductionPlanStatus::FINALIZED;
    }

    public function canBeFinalized(): bool
    {
        return $this->status === ProductionPlanStatus::PLANNED;
    }

    public function scopeForSppg($query, int $sppgId)
    {
        return $query->where('sppg_id', $sppgId);
    }

    public function scopeForDate($query, $date)
    {
        $dateStr = $date instanceof Carbon ? $date->format('Y-m-d') : $date;
        return $query->whereDate('service_date', $dateStr);
    }
}
