<?php

namespace App\Enums;

enum ProductionPlanStatus: string
{
    case PLANNED = 'PLANNED';
    case FINALIZED = 'FINALIZED';
    case IN_PRODUCTION = 'IN_PRODUCTION';
    case READY_FOR_DISTRIBUTION = 'READY_FOR_DISTRIBUTION';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::PLANNED => 'Rencana Awal',
            self::FINALIZED => 'Rencana Final',
            self::IN_PRODUCTION => 'Dalam Produksi Dapur',
            self::READY_FOR_DISTRIBUTION => 'Siap Distribusi',
            self::COMPLETED => 'Produksi Selesai',
            self::CANCELLED => 'Rencana Dibatalkan',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::PLANNED => 'bg-[#FEF6EE] text-[#B54708] border-[#F9DBAF]',
            self::FINALIZED => 'bg-[#EDF7F2] text-[#1B573E] border-[#BCE2D0]',
            self::IN_PRODUCTION => 'bg-[#F0F5FA] text-[#285F80] border-[#BCD4E6]',
            self::READY_FOR_DISTRIBUTION => 'bg-[#FAF4EB] text-[#885D1C] border-[#E8D7BE]',
            self::COMPLETED => 'bg-[#EDF7F2] text-[#1B573E] border-[#BCE2D0]',
            self::CANCELLED => 'bg-[#FDF2F2] text-[#9B3232] border-[#F5C2C2]',
        };
    }

    public function dotClasses(): string
    {
        return match ($this) {
            self::PLANNED => 'bg-[#F79009]',
            self::FINALIZED => 'bg-[#12B76A]',
            self::IN_PRODUCTION => 'bg-[#285F80]',
            self::READY_FOR_DISTRIBUTION => 'bg-[#885D1C]',
            self::COMPLETED => 'bg-[#12B76A]',
            self::CANCELLED => 'bg-[#D92D20]',
        };
    }

    public function isFinalized(): bool
    {
        return $this === self::FINALIZED;
    }

    public function canBeFinalized(): bool
    {
        return $this === self::PLANNED;
    }

    public function isLocked(): bool
    {
        return in_array($this, [self::FINALIZED, self::IN_PRODUCTION, self::READY_FOR_DISTRIBUTION, self::COMPLETED], true);
    }
}
