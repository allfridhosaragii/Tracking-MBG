<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sppg extends Model
{
    use HasFactory;

    protected $table = 'sppgs';

    protected $fillable = [
        'code',
        'name',
        'address',
        'latitude',
        'longitude',
        'max_daily_portions',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'max_daily_portions' => 'integer',
        'is_active' => 'boolean',
    ];

    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    public function productionPlans(): HasMany
    {
        return $this->hasMany(ProductionPlan::class);
    }
}
