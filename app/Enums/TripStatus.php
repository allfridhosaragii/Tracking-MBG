<?php

namespace App\Enums;

enum TripStatus: string
{
    case DRAFT = 'DRAFT';
    case ASSIGNED = 'ASSIGNED';
    case VEHICLE_CHECKED = 'VEHICLE_CHECKED';
    case READY = 'READY';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case INTERRUPTED = 'INTERRUPTED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Konsep',
            self::ASSIGNED => 'Ditugaskan',
            self::VEHICLE_CHECKED => 'Kendaraan Diperiksa',
            self::READY => 'Siap Berangkat',
            self::IN_PROGRESS => 'Dalam Perjalanan',
            self::COMPLETED => 'Selesai',
            self::INTERRUPTED => 'Terganggu',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-slate-100 text-slate-700 border-slate-300',
            self::ASSIGNED => 'bg-blue-50 text-blue-700 border-blue-200',
            self::VEHICLE_CHECKED => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            self::READY => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::IN_PROGRESS => 'bg-amber-50 text-amber-700 border-amber-200',
            self::COMPLETED => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            self::INTERRUPTED => 'bg-red-50 text-red-700 border-red-200',
            self::CANCELLED => 'bg-slate-200 text-slate-600 border-slate-300',
        };
    }
}
