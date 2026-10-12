<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
}
