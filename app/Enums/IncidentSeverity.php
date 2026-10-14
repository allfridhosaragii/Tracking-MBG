<?php

namespace App\Enums;

enum IncidentSeverity: string
{
    case LOW = 'LOW';
    case MEDIUM = 'MEDIUM';
    case HIGH = 'HIGH';
    case CRITICAL = 'CRITICAL';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Rendah',
            self::MEDIUM => 'Sedang',
            self::HIGH => 'Tinggi',
            self::CRITICAL => 'Kritis',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::LOW => 'bg-slate-100 text-slate-700 border-slate-300',
            self::MEDIUM => 'bg-amber-50 text-amber-800 border-amber-200',
            self::HIGH => 'bg-orange-50 text-orange-800 border-orange-200',
            self::CRITICAL => 'bg-rose-50 text-rose-800 border-rose-300',
        };
    }
}
