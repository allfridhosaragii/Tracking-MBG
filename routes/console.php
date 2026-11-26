<?php

use App\Services\CacheMaintenanceService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:purge-stale-cache {--force : Paksa bersihkan cache aplikasi dan compiled views sekarang}', function (CacheMaintenanceService $cacheMaintenance) {
    $result = $cacheMaintenance->purgeIfDue((bool) $this->option('force'));
    if ($result['purged']) {
        $this->info("Auto-purge selesai pada {$result['purged_at']} ({$result['views_deleted']} compiled view dibersihkan).");
    } else {
        $this->line("Belum memasuki interval 5 menit (terakhir dibersihkan: {$result['purged_at']}).");
    }
})->purpose('Purge application cache and compiled views every 5 minutes');

Schedule::command('app:purge-stale-cache')->everyFiveMinutes();

Artisan::command('trays:backfill {--school_id= : Scope backfill to specific school}', function () {
    $query = \App\Models\SchoolServiceRecord::doesntHave('trayReturn');
    if ($schoolId = $this->option('school_id')) {
        $query->whereHas('tripStop', fn($q) => $q->where('school_id', $schoolId));
    }
    $records = $query->get();
    $count = 0;
    $service = app(\App\Services\TrayReturnService::class);
    foreach ($records as $record) {
        $service->initiateForRecord($record, null, false);
        $count++;
    }
    $this->info("Berhasil melakukan backfill {$count} catatan wadah tanpa asumsi fisik palsu.");
})->purpose('Backfill missing TrayReturn records safely without assuming expected physical containers');
