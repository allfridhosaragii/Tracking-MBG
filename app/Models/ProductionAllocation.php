<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_plan_id',
        'portion_request_id',
        'school_id',
        'allocated_portions',
    ];

    protected $casts = [
        'allocated_portions' => 'integer',
    ];

    public function productionPlan(): BelongsTo
    {
        return $this->belongsTo(ProductionPlan::class);
    }

    public function portionRequest(): BelongsTo
    {
        return $this->belongsTo(DailyPortionRequest::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
