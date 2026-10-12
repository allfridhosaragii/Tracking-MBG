<?php

namespace App\Models;

use App\Enums\PortionRequestStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyPortionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'service_date',
        'registered_count',
        'present_count',
        'requested_portions',
        'status',
        'confirmed_by',
        'confirmed_at',
        'locked_at',
        'revision_reason',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'status' => PortionRequestStatus::class,
            'registered_count' => 'integer',
            'present_count' => 'integer',
            'requested_portions' => 'integer',
            'confirmed_at' => 'datetime',
            'locked_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeForServiceDate(Builder $query, string|Carbon $date): Builder
    {
        $dateStr = $date instanceof Carbon ? $date->format('Y-m-d') : $date;
        return $query->whereDate('service_date', $dateStr);
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->whereIn('status', [
            PortionRequestStatus::CONFIRMED,
            PortionRequestStatus::LOCKED,
            PortionRequestStatus::REVISED,
        ]);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PortionRequestStatus::DRAFT);
    }

    public function isDraft(): bool
    {
        return $this->status === PortionRequestStatus::DRAFT;
    }

    public function isConfirmed(): bool
    {
        return $this->status === PortionRequestStatus::CONFIRMED;
    }

    public function isLocked(): bool
    {
        return $this->status === PortionRequestStatus::LOCKED;
    }

    public function isRevised(): bool
    {
        return $this->status === PortionRequestStatus::REVISED;
    }

    public function isCancelled(): bool
    {
        return $this->status === PortionRequestStatus::CANCELLED;
    }

    /**
     * Calculate cutoff datetime for this service date (Asia/Jakarta timezone).
     * Defaults to H-1 at 16:00 WIB.
     */
    public function cutoffDateTime(): Carbon
    {
        $cutoffDays = (int) config('mbg.portion_cutoff_days_prior', 1);
        $cutoffTime = (string) config('mbg.portion_cutoff_time', '16:00');

        [$hours, $minutes] = explode(':', $cutoffTime);

        return Carbon::parse($this->service_date, 'Asia/Jakarta')
            ->subDays($cutoffDays)
            ->setTime((int) $hours, (int) $minutes, 0);
    }

    /**
     * Determine if current time has passed the confirmation cutoff.
     */
    public function isPastCutoff(?Carbon $currentTime = null): bool
    {
        $now = $currentTime ?? Carbon::now('Asia/Jakarta');
        return $now->greaterThanOrEqualTo($this->cutoffDateTime());
    }

    /**
     * Determine if this request can be edited by PIC normally.
     * Can only be edited if DRAFT and before cutoff.
     */
    public function canBeEditedNormally(?Carbon $now = null): bool
    {
        return $this->isDraft() && ! $this->isPastCutoff($now);
    }

    /**
     * Determine if this request can be revised.
     * Can be revised if CONFIRMED or LOCKED, but requires revision reason.
     */
    public function canBeRevised(): bool
    {
        return in_array($this->status, [
            PortionRequestStatus::CONFIRMED,
            PortionRequestStatus::LOCKED,
            PortionRequestStatus::REVISED,
        ], true);
    }
}
