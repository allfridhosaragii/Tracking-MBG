<?php

namespace App\Models;

use App\Enums\ProductionStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionStageLog extends Model
{
    protected $fillable = [
        'food_batch_id',
        'stage',
        'started_at',
        'completed_at',
        'updated_by',
        'notes',
    ];

    protected $casts = [
        'stage' => ProductionStage::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function foodBatch(): BelongsTo
    {
        return $this->belongsTo(FoodBatch::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
