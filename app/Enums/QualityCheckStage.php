<?php

namespace App\Enums;

enum QualityCheckStage: string
{
    case BEFORE_DISPATCH = 'BEFORE_DISPATCH';
    case ON_ARRIVAL = 'ON_ARRIVAL';
    case BEFORE_CONSUMPTION = 'BEFORE_CONSUMPTION';
    case PRE_CONSUMPTION = 'PRE_CONSUMPTION';

    public function label(): string
    {
        return match ($this) {
            self::BEFORE_DISPATCH => 'Pemeriksaan Sebelum Distribusi (Dapur SPPG)',
            self::ON_ARRIVAL => 'Pemeriksaan Saat Kedatangan (Sekolah)',
            self::BEFORE_CONSUMPTION, self::PRE_CONSUMPTION => 'Pemeriksaan Sebelum Konsumsi Siswa (Sekolah)',
        };
    }
}
