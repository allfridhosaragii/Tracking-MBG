<?php

namespace App\Enums;

enum TripType: string
{
    case DELIVERY = 'DELIVERY';
    case PICKUP = 'PICKUP';
    case MIXED = 'MIXED';

    public function label(): string
    {
        return match ($this) {
            self::DELIVERY => 'Pengantaran Makanan',
            self::PICKUP => 'Penjemputan Ompreng',
            self::MIXED => 'Gabungan (Antar & Jemput)',
        };
    }
}
