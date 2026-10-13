<?php

namespace App\Enums;

enum TripStopStatus: string
{
    case PENDING = 'PENDING';
    case EN_ROUTE = 'EN_ROUTE';
    case ARRIVED = 'ARRIVED';
    case ARRIVED_AWAITING_CONFIRMATION = 'ARRIVED_AWAITING_CONFIRMATION';
    case HANDED_OVER = 'HANDED_OVER';
    case COMPLETED = 'COMPLETED';
    case SKIPPED = 'SKIPPED';
    case EXCEPTION = 'EXCEPTION';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Giliran',
            self::EN_ROUTE => 'Dalam Menuju Lokasi',
            self::ARRIVED => 'Tiba di Sekolah',
            self::ARRIVED_AWAITING_CONFIRMATION => 'Menunggu Konfirmasi PIC',
            self::HANDED_OVER => 'Makanan Diserahkan',
            self::COMPLETED => 'Selesai',
            self::SKIPPED => 'Dilewati',
            self::EXCEPTION => 'Terdapat Kendala',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'bg-slate-100 text-slate-700 border-slate-300',
            self::EN_ROUTE => 'bg-blue-50 text-blue-700 border-blue-200',
            self::ARRIVED => 'bg-amber-50 text-amber-700 border-amber-200',
            self::ARRIVED_AWAITING_CONFIRMATION => 'bg-yellow-50 text-yellow-800 border-yellow-200',
            self::HANDED_OVER => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::COMPLETED => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            self::SKIPPED => 'bg-slate-200 text-slate-600 border-slate-300',
            self::EXCEPTION => 'bg-rose-50 text-rose-700 border-rose-200',
        };
    }
}
