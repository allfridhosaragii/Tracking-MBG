<?php

namespace App\Enums;

enum QualityCheckConclusion: string
{
    case SAFE = 'SAFE';
    case UNSAFE = 'UNSAFE';
    case HOLD = 'HOLD';

    public function label(): string
    {
        return match ($this) {
            self::SAFE => 'Lolos QC (Aman Didistribusikan)',
            self::UNSAFE => 'Tidak Lolos QC (Bahaya Keamanan Pangan)',
            self::HOLD => 'Ditahan (Perlu Evaluasi Ulang)',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::SAFE => 'bg-[#ECFDF5] text-[#047857] border-[#A7F3D0]',
            self::UNSAFE => 'bg-[#FEF2F2] text-[#B91C1C] border-[#FECACA]',
            self::HOLD => 'bg-[#FFFBEB] text-[#B45309] border-[#FDE68A]',
        };
    }

    public function badgeClass(): string
    {
        return $this->badgeClasses();
    }

    public function isSafe(): bool

    {
        return $this === self::SAFE;
    }
}
