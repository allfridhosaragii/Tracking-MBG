<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class DeliveryItem extends Model
{
    protected $fillable = [
        'food_batch_id',
        'production_allocation_id',
        'trip_stop_id',
        'planned_portions',
        'loaded_portions',
        'delivered_portions',
        'status',
    ];

    protected $casts = [
        'planned_portions' => 'integer',
        'loaded_portions' => 'integer',
        'delivered_portions' => 'integer',
    ];

    public function foodBatch(): BelongsTo
    {
        return $this->belongsTo(FoodBatch::class);
    }

    public function productionAllocation(): BelongsTo
    {
        return $this->belongsTo(ProductionAllocation::class);
    }

    public function school(): HasOneThrough
    {
        return $this->hasOneThrough(
            School::class,
            ProductionAllocation::class,
            'id', // Foreign key on production_allocations table...
            'id', // Foreign key on schools table...
            'production_allocation_id', // Local key on delivery_items table...
            'school_id' // Local key on production_allocations table...
        );
    }

    public function isPendingDispatch(): bool
    {
        return $this->status === 'PENDING_DISPATCH' || $this->trip_stop_id === null;
    }
}
