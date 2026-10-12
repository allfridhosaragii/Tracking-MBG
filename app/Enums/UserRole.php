<?php

namespace App\Enums;

enum UserRole: string
{
    case KEPALA_SPPG = 'KEPALA_SPPG';
    case PENGAWAS_GIZI = 'PENGAWAS_GIZI';
    case ASISTEN_LAPANGAN = 'ASISTEN_LAPANGAN';
    case KURIR = 'KURIR';
    case PIC_SEKOLAH = 'PIC_SEKOLAH';

    public function label(): string
    {
        return match ($this) {
            self::KEPALA_SPPG => 'Kepala SPPG',
            self::PENGAWAS_GIZI => 'Pengawas Gizi',
            self::ASISTEN_LAPANGAN => 'Asisten Lapangan',
            self::KURIR => 'Kurir',
            self::PIC_SEKOLAH => 'PIC Sekolah',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::KEPALA_SPPG => 'Operations Briefing & Kontrol SPPG',
            self::PENGAWAS_GIZI => 'Review Desk & Verifikasi Mutu/Gizi',
            self::ASISTEN_LAPANGAN => 'Production Runbook & Tahapan Fisik',
            self::KURIR => 'Active Mission & Pengiriman Multi-Stop',
            self::PIC_SEKOLAH => 'Receipt Workflow & Konfirmasi Sekolah',
        };
    }

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::KEPALA_SPPG => 'head.dashboard',
            self::PENGAWAS_GIZI => 'nutrition.dashboard',
            self::ASISTEN_LAPANGAN => 'production.dashboard',
            self::KURIR => 'driver.dashboard',
            self::PIC_SEKOLAH => 'school.dashboard',
        };
    }

    public function isInternalSppg(): bool
    {
        return in_array($this, [
            self::KEPALA_SPPG,
            self::PENGAWAS_GIZI,
            self::ASISTEN_LAPANGAN,
            self::KURIR,
        ], true);
    }

    public function isSchoolPic(): bool
    {
        return $this === self::PIC_SEKOLAH;
    }
}
