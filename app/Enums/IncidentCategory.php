<?php

namespace App\Enums;

enum IncidentCategory: string
{
    case FOOD_SAFETY = 'FOOD_SAFETY';
    case PORTION_SHORTAGE = 'PORTION_SHORTAGE';
    case PORTION_DAMAGED = 'PORTION_DAMAGED';
    case TRAY_DISCREPANCY = 'TRAY_DISCREPANCY';
    case DELIVERY_DELAY = 'DELIVERY_DELAY';
    case VEHICLE_BREAKDOWN = 'VEHICLE_BREAKDOWN';
    case SCHOOL_CLOSED = 'SCHOOL_CLOSED';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::FOOD_SAFETY => 'Keamanan Pangan (Food Safety)',
            self::PORTION_SHORTAGE => 'Kekurangan Porsi Fisik',
            self::PORTION_DAMAGED => 'Kerusakan Makanan / Porsi',
            self::TRAY_DISCREPANCY => 'Selisih Wadah / Ompreng',
            self::DELIVERY_DELAY => 'Keterlambatan Pengantaran',
            self::VEHICLE_BREAKDOWN => 'Kendala Kendaraan / Armada',
            self::SCHOOL_CLOSED => 'Sekolah Tutup / Libur Mendadak',
            self::OTHER => 'Kendala Lainnya',
        };
    }
}
