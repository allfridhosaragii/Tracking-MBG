<?php

namespace Tests\Feature;

use App\Services\CacheMaintenanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AutoCachePurgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_request_purges_cache_automatically_when_five_minutes_have_elapsed(): void
    {
        $service = app(CacheMaintenanceService::class);
        $marker = $service->markerPath();

        @mkdir(dirname($marker), 0755, true);
        @file_put_contents($marker, date('c', time() - 360), LOCK_EX);
        @touch($marker, time() - 360);

        Cache::put('stale_demo_key', 'old-value', 600);
        $this->assertSame('old-value', Cache::get('stale_demo_key'));

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertHeader('X-Auto-Cache-Interval', '300s');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertNull(Cache::get('stale_demo_key'));
    }

    public function test_cache_is_preserved_within_five_minute_window_and_forced_via_command(): void
    {
        $service = app(CacheMaintenanceService::class);
        $marker = $service->markerPath();

        @mkdir(dirname($marker), 0755, true);
        @file_put_contents($marker, date('c'), LOCK_EX);
        @touch($marker, time());

        Cache::put('fresh_key', 'still-valid', 600);

        $this->get('/login')->assertStatus(200);
        $this->assertSame('still-valid', Cache::get('fresh_key'));

        $this->artisan('app:purge-stale-cache --force')->assertExitCode(0);
        $this->assertNull(Cache::get('fresh_key'));
    }
}
