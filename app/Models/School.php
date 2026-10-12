<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    protected $table = 'schools';

    protected $fillable = [
        'sppg_id',
        'school_code',
        'name',
        'education_level',
        'address',
        'latitude',
        'longitude',
        'registered_beneficiaries',
        'public_slug',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'registered_beneficiaries' => 'integer',
        'is_active' => 'boolean',
    ];

    public function sppg(): BelongsTo
    {
        return $this->belongsTo(Sppg::class);
    }

    public function picUsers(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
