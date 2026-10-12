<?php

namespace App\Enums;

enum MenuStatus: string
{
    case DRAFT = 'DRAFT';
    case ACTIVE = 'ACTIVE';
    case ARCHIVED = 'ARCHIVED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft Penyusunan',
            self::ACTIVE => 'Aktif & Sah',
            self::ARCHIVED => 'Arsip Versi Lama',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-[#FEF6EE] text-[#B54708] border-[#F9DBAF]',
            self::ACTIVE => 'bg-[#EDF7F2] text-[#1B573E] border-[#BCE2D0]',
            self::ARCHIVED => 'bg-[#F2F4F7] text-[#475467] border-[#D0D5DD]',
        };
    }

    public function dotClasses(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-[#F79009]',
            self::ACTIVE => 'bg-[#12B76A]',
            self::ARCHIVED => 'bg-[#98A2B3]',
        };
    }
}
