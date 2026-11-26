<?php

namespace App\Http\Middleware;

use App\Services\CacheMaintenanceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutoPurgeCacheMiddleware
{
    public function __construct(
        private readonly CacheMaintenanceService $cacheMaintenance,
    ) {}

    /**
     * Enforce a 5-minute automatic cache sweep and prevent stale HTML document caching.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->cacheMaintenance->purgeIfDue();

        $response = $next($request);

        $contentType = (string) $response->headers->get('Content-Type', '');
        if ($contentType === '' || str_contains($contentType, 'text/html')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
            $response->headers->set('X-Auto-Cache-Interval', CacheMaintenanceService::PURGE_INTERVAL_SECONDS . 's');
        }

        return $response;
    }
}
