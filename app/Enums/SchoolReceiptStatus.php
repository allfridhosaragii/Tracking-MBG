<?php

namespace App\Enums;

enum SchoolReceiptStatus: string
{
    case WAITING = 'WAITING';
    case RECEIVED = 'RECEIVED';
    case RECEIVED_WITH_DISCREPANCY = 'RECEIVED_WITH_DISCREPANCY';
    case HELD = 'HELD';
    case REJECTED = 'REJECTED';
    case CLEARED_TO_SERVE = 'CLEARED_TO_SERVE';
    case DISTRIBUTED = 'DISTRIBUTED';
    case MEAL_COMPLETED = 'MEAL_COMPLETED';

    public function label(): string
    {
        return match ($this) {
            self::WAITING => 'Menunggu Penerimaan',
            self::RECEIVED => 'Diterima Lengkap',
            self::RECEIVED_WITH_DISCREPANCY => 'Diterima dengan Selisih',
            self::HELD => 'Ditahan (Kendala Keamanan Pangan)',
            self::REJECTED => 'Ditolak',
            self::CLEARED_TO_SERVE => 'Lolos Uji - Siap Dibagikan',
            self::DISTRIBUTED => 'Sedang / Telah Dibagikan',
            self::MEAL_COMPLETED => 'Layanan Selesai',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::WAITING => 'bg-amber-50 text-amber-800 border-amber-200',
            self::RECEIVED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::RECEIVED_WITH_DISCREPANCY => 'bg-yellow-50 text-yellow-800 border-yellow-200',
            self::HELD => 'bg-rose-50 text-rose-700 border-rose-200',
            self::REJECTED => 'bg-rose-100 text-rose-800 border-rose-300',
            self::CLEARED_TO_SERVE => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::DISTRIBUTED => 'bg-blue-50 text-blue-700 border-blue-200',
            self::MEAL_COMPLETED => 'bg-slate-100 text-slate-700 border-slate-300',
        };
    }
}
