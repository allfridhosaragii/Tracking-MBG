<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class CacheMaintenanceService
{
    public const PURGE_INTERVAL_SECONDS = 300;

    /**
     * Purge application cache and compiled views if at least 5 minutes have elapsed.
     *
     * @return array{purged: bool, views_deleted: int, purged_at: string|null}
     */
    public function purgeIfDue(bool $force = false): array
    {
        $markerPath = $this->markerPath();
        $now = time();

        if (! $force && file_exists($markerPath)) {
            $lastPurgedAt = @filemtime($markerPath);
            if ($lastPurgedAt !== false && ($now - $lastPurgedAt) < self::PURGE_INTERVAL_SECONDS) {
                return [
                    'purged' => false,
                    'views_deleted' => 0,
                    'purged_at' => date('c', $lastPurgedAt),
                ];
            }
        }

        $markerDir = dirname($markerPath);
        if (! is_dir($markerDir)) {
            @mkdir($markerDir, 0755, true);
        }

        @file_put_contents($markerPath, date('c', $now), LOCK_EX);
        @touch($markerPath, $now);

        Cache::flush();

        $viewsDeleted = $this->clearCompiledViews();

        if (app()->runningInConsole() && $force) {
            Artisan::call('clear-compiled');
        }

        return [
            'purged' => true,
            'views_deleted' => $viewsDeleted,
            'purged_at' => date('c', $now),
        ];
    }

    /**
     * Resolve writable timestamp marker location for both local and serverless runtimes.
     */
    public function markerPath(): string
    {
        $compiledPath = (string) config('view.compiled', storage_path('framework/views'));

        if (str_starts_with($compiledPath, '/tmp')) {
            return '/tmp/mbg_track_cache_purge.stamp';
        }

        return storage_path('framework/cache/mbg_track_cache_purge.stamp');
    }

    /**
     * Delete compiled Blade view files without removing directory gitignore markers.
     */
    protected function clearCompiledViews(): int
    {
        $compiledPath = (string) config('view.compiled', '');
        if ($compiledPath === '' || ! is_dir($compiledPath)) {
            return 0;
        }

        $deleted = 0;
        foreach (File::glob($compiledPath . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
            if (@unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }
}
