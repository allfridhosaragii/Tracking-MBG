<?php

namespace App\Enums;

enum ProductionStage: string
{
    case PREPARATION = 'PREPARATION';
    case COOKING = 'COOKING';
    case PORTIONING = 'PORTIONING';
    case PACKING = 'PACKING';
    case READY_FOR_QC = 'READY_FOR_QC';

    public function label(): string
    {
        return match ($this) {
            self::PREPARATION => 'Tahap 1: Persiapan Bahan',
            self::COOKING => 'Tahap 2: Pengolahan & Memasak',
            self::PORTIONING => 'Tahap 3: Pemorsian Higienis',
            self::PACKING => 'Tahap 4: Pengemasan & Pelabelan',
            self::READY_FOR_QC => 'Tahap 5: Penyerahan ke Pengawas Gizi (QC)',
        };
    }

    public function next(): ?self
    {
        return match ($this) {
            self::PREPARATION => self::COOKING,
            self::COOKING => self::PORTIONING,
            self::PORTIONING => self::PACKING,
            self::PACKING => self::READY_FOR_QC,
            self::READY_FOR_QC => null,
        };
    }
}
