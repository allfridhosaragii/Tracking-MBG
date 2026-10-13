<?php

namespace App\Enums;

enum FoodBatchStatus: string
{
    case PREPARING = 'PREPARING';
    case COOKING = 'COOKING';
    case PORTIONING = 'PORTIONING';
    case PACKED = 'PACKED';
    case QC_CHECK = 'QC_CHECK';
    case RELEASED = 'RELEASED';
    case ON_HOLD = 'ON_HOLD';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::PREPARING => 'Persiapan Bahan',
            self::COOKING => 'Proses Memasak',
            self::PORTIONING => 'Pemorsian',
            self::PACKED => 'Selesai Dikemas',
            self::QC_CHECK => 'Pemeriksaan Mutu (QC)',
            self::RELEASED => 'Lolos QC & Siap Distribusi',
            self::ON_HOLD => 'Ditahan (Perlu Evaluasi Mutu)',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::PREPARING => 'bg-[#F0F4F2] text-[#24523B] border-[#D3E1DA]',
            self::COOKING => 'bg-[#FFF7ED] text-[#C2410C] border-[#FFEDD5]',
            self::PORTIONING => 'bg-[#EFF6FF] text-[#1D4ED8] border-[#DBEAFE]',
            self::PACKED => 'bg-[#FAF5FF] text-[#7E22CE] border-[#F3E8FF]',
            self::QC_CHECK => 'bg-[#FEFCE8] text-[#A16207] border-[#FEF08A]',
            self::RELEASED => 'bg-[#ECFDF5] text-[#047857] border-[#A7F3D0]',
            self::ON_HOLD => 'bg-[#FEF2F2] text-[#B91C1C] border-[#FECACA]',
            self::CANCELLED => 'bg-[#F3F4F6] text-[#4B5563] border-[#E5E7EB]',
        };
    }

    public function isReleased(): bool
    {
        return $this === self::RELEASED;
    }

    public function isOnHold(): bool
    {
        return $this === self::ON_HOLD;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::RELEASED, self::CANCELLED], true);
    }
}
