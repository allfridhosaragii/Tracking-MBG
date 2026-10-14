<?php

namespace App\Enums;

enum TrayReturnStatus: string
{
    case IN_USE = 'IN_USE';
    case READY_FOR_PICKUP = 'READY_FOR_PICKUP';
    case PICKUP_ASSIGNED = 'PICKUP_ASSIGNED';
    case PICKED_UP = 'PICKED_UP';
    case RETURNED_TO_SPPG = 'RETURNED_TO_SPPG';
    case DISCREPANCY = 'DISCREPANCY';

    public function label(): string
    {
        return match ($this) {
            self::IN_USE => 'Dalam Penggunaan',
            self::READY_FOR_PICKUP => 'Siap Dijemput',
            self::PICKUP_ASSIGNED => 'Penugasan Penjemputan',
            self::PICKED_UP => 'Wadah Diambil',
            self::RETURNED_TO_SPPG => 'Kembali ke SPPG',
            self::DISCREPANCY => 'Terdapat Selisih',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::IN_USE => 'bg-slate-100 text-slate-700 border-slate-300',
            self::READY_FOR_PICKUP => 'bg-amber-50 text-amber-800 border-amber-200',
            self::PICKUP_ASSIGNED => 'bg-blue-50 text-blue-700 border-blue-200',
            self::PICKED_UP => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            self::RETURNED_TO_SPPG => 'bg-emerald-50 text-emerald-800 border-emerald-300',
            self::DISCREPANCY => 'bg-rose-50 text-rose-700 border-rose-200',
        };
    }
}
