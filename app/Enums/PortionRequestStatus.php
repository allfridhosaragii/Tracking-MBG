<?php

namespace App\Enums;

enum PortionRequestStatus: string
{
    case DRAFT = 'DRAFT';
    case CONFIRMED = 'CONFIRMED';
    case LOCKED = 'LOCKED';
    case REVISED = 'REVISED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft Permintaan',
            self::CONFIRMED => 'Terkonfirmasi PIC',
            self::LOCKED => 'Terkunci Produksi',
            self::REVISED => 'Revisi Terotorisasi',
            self::CANCELLED => 'Layanan Dibatalkan',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-[#FEF6EE] text-[#B54708] border-[#F9DBAF]',
            self::CONFIRMED => 'bg-[#EDF7F2] text-[#1B573E] border-[#BCE2D0]',
            self::LOCKED => 'bg-[#F0F5FA] text-[#285F80] border-[#BCD4E6]',
            self::REVISED => 'bg-[#FAF4EB] text-[#885D1C] border-[#E8D7BE]',
            self::CANCELLED => 'bg-[#FDF2F2] text-[#9B3232] border-[#F5C2C2]',
        };
    }

    public function dotClasses(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-[#F79009]',
            self::CONFIRMED => 'bg-[#12B76A]',
            self::LOCKED => 'bg-[#285F80]',
            self::REVISED => 'bg-[#885D1C]',
            self::CANCELLED => 'bg-[#D92D20]',
        };
    }

    public function isConfirmedOrHigher(): bool
    {
        return in_array($this, [self::CONFIRMED, self::LOCKED, self::REVISED], true);
    }
}
