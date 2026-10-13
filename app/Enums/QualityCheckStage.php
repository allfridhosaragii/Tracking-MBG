<?php

namespace App\Enums;

enum QualityCheckStage: string
{
    case BEFORE_DISPATCH = 'BEFORE_DISPATCH';
    case ON_ARRIVAL = 'ON_ARRIVAL';
    case PRE_CONSUMPTION = 'PRE_CONSUMPTION';

    public function label(): string
    {
        return match ($this) {
            self::BEFORE_DISPATCH => 'Pemeriksaan Sebelum Distribusi (Dapur SPPG)',
            self::ON_ARRIVAL => 'Pemeriksaan Saat Kedatangan (Sekolah)',
            self::PRE_CONSUMPTION => 'Pemeriksaan Sebelum Konsumsi Siswa (Sekolah)',
        };
    }
}
