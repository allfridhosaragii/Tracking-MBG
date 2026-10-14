<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
