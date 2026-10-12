<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'sppg_id',
        'school_id',
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sppg(): BelongsTo
    {
        return $this->belongsTo(Sppg::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function hasRole(UserRole|string $role): bool
    {
        $roleValue = $role instanceof UserRole ? $role->value : $role;
        return $this->role?->value === $roleValue || $this->role === $roleValue;
    }

    public function isInternalStaff(): bool
    {
        return $this->role?->isInternalSppg() ?? false;
    }

    public function isSchoolPic(): bool
    {
        return $this->role === UserRole::PIC_SEKOLAH;
    }

    public function isKepala(): bool
    {
        return $this->role === UserRole::KEPALA_SPPG;
    }

    /**
     * Get the tenant SPPG ID.
     * For internal users: sppg_id.
     * For PIC sekolah: derived from school->sppg_id if sppg_id not set directly.
     */
    public function getEffectiveSppgId(): ?int
    {
        if ($this->sppg_id) {
            return (int) $this->sppg_id;
        }

        return $this->school?->sppg_id ? (int) $this->school->sppg_id : null;
    }

    /**
     * Audit trail logs recorded on this user as target.
     */
    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'auditable');
    }

    /**
     * Audit trail logs performed by this user as actor.
     */
    public function recordedActivities(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'actor_id');
    }

    /**
     * Check if driver is currently assigned to an active trip.
     */
    public function hasActiveTrips(): bool
    {
        if (Schema::hasTable('trips')) {
            return DB::table('trips')
                ->where('driver_id', $this->id)
                ->whereIn('status', ['DISPATCHED', 'EN_ROUTE', 'IN_TRANSIT', 'DELIVERING'])
                ->exists();
        }

        return false;
    }

    /**
     * Check if this user is the only active Kepala SPPG in their SPPG.
     */
    public function isSoleActiveHead(): bool
    {
        if ($this->role !== UserRole::KEPALA_SPPG || ! $this->is_active) {
            return false;
        }

        return self::where('sppg_id', $this->sppg_id)
            ->where('role', UserRole::KEPALA_SPPG->value)
            ->where('is_active', true)
            ->count() <= 1;
    }
}
