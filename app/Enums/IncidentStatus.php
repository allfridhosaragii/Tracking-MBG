<?php

namespace App\Enums;

enum IncidentStatus: string
{
    case OPEN = 'OPEN';
    case ACKNOWLEDGED = 'ACKNOWLEDGED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case RESOLVED = 'RESOLVED';
    case CLOSED = 'CLOSED';
    case REOPENED = 'REOPENED';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Terbuka',
            self::ACKNOWLEDGED => 'Diterima',
            self::IN_PROGRESS => 'Sedang Ditindaklanjuti',
            self::RESOLVED => 'Terselesaikan',
            self::CLOSED => 'Ditutup',
            self::REOPENED => 'Dibuka Kembali',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::OPEN => 'bg-rose-50 text-rose-700 border-rose-200',
            self::ACKNOWLEDGED => 'bg-amber-50 text-amber-800 border-amber-200',
            self::IN_PROGRESS => 'bg-blue-50 text-blue-700 border-blue-200',
            self::RESOLVED => 'bg-emerald-50 text-emerald-800 border-emerald-300',
            self::CLOSED => 'bg-slate-100 text-slate-700 border-slate-300',
            self::REOPENED => 'bg-purple-50 text-purple-700 border-purple-200',
        };
    }
}
