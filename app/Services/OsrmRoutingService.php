<?php

namespace App\Services;

use App\Models\Trip;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class OsrmRoutingService
{
    protected string $baseUrl;
    protected string $profile;
    protected int $timeout;
    protected int $cacheTtl;
    protected int $failureCacheTtl;

    protected string $fallbackUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.osrm.base_url', 'https://routing.openstreetmap.de/routed-car'), '/');
        $this->fallbackUrl = rtrim(config('services.osrm.fallback_url', 'https://router.project-osrm.org'), '/');
        $this->profile = config('services.osrm.profile', 'driving');
        $this->timeout = (int) config('services.osrm.timeout', 4);
        $this->cacheTtl = (int) config('services.osrm.cache_ttl', 7200); // 2 hours default
        $this->failureCacheTtl = (int) config('services.osrm.failure_cache_ttl', 60); // 60s failure TTL
    }

    /**
     * Get route between ordered waypoints using OSRM.
     * Waypoints format: [ ['lat' => float, 'lng' => float, 'name' => string], ... ]
     *
     * @param  array<int, array{lat: float|string, lng: float|string, name?: string}>  $waypoints
     * @return array{
     *     success: bool,
     *     distance_m: int|null,
     *     duration_s: int|null,
     *     geometry: array|null,
     *     waypoints: array,
     *     legs: array,
     *     error: string|null
     * }
     */
    public function getRoute(array $waypoints): array
    {
        if (count($waypoints) < 2) {
            return [
                'success' => false,
                'distance_m' => 0,
                'duration_s' => 0,
                'geometry' => null,
                'waypoints' => $waypoints,
                'legs' => [],
                'error' => 'Minimal dua titik lokasi koordinat diperlukan untuk merutekan perjalanan.',
            ];
        }

        // Validate coordinates strictly
        $coordinatePairs = [];
        foreach ($waypoints as $index => $point) {
            $lat = filter_var($point['lat'] ?? null, FILTER_VALIDATE_FLOAT);
            $lng = filter_var($point['lng'] ?? null, FILTER_VALIDATE_FLOAT);

            if ($lat === false || $lng === false) {
                throw new InvalidArgumentException("Titik lokasi indeks {$index} memiliki format koordinat tidak valid.");
            }

            if ($lat < -90.0 || $lat > 90.0 || $lng < -180.0 || $lng > 180.0) {
                throw new InvalidArgumentException("Koordinat titik indeks {$index} [{$lat}, {$lng}] berada di luar rentang geografis bumi.");
            }

            // Reject invalid placeholder coordinates (0.0, 0.0)
            if (abs($lat) < 0.0001 && abs($lng) < 0.0001) {
                throw new InvalidArgumentException("Koordinat titik indeks {$index} bernilai placeholder (0,0). Diperlukan koordinat riil.");
            }

            // OSRM expects {longitude},{latitude}
            $coordinatePairs[] = sprintf('%.6f,%.6f', $lng, $lat);
        }

        $coordString = implode(';', $coordinatePairs);
        $cacheKey = $this->getCacheKey($waypoints);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $url = "{$this->baseUrl}/route/v1/{$this->profile}/{$coordString}?overview=full&geometries=geojson&steps=true";

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->get($url);

            if (! $response->successful() && $this->fallbackUrl && $this->fallbackUrl !== $this->baseUrl) {
                $fallbackFullUrl = "{$this->fallbackUrl}/route/v1/{$this->profile}/{$coordString}?overview=full&geometries=geojson&steps=true";
                try {
                    $response = Http::timeout($this->timeout)
                        ->acceptJson()
                        ->get($fallbackFullUrl);
                } catch (\Throwable $e) {
                    // Fallback also failed, proceed with original response check
                }
            }

            if (! $response->successful()) {
                Log::warning('OSRM request unsuccessful', [
                    'status' => $response->status(),
                    'coord_count' => count($coordinatePairs),
                ]);

                $failureResult = [
                    'success' => false,
                    'distance_m' => null,
                    'duration_s' => null,
                    'geometry' => null,
                    'waypoints' => $waypoints,
                    'legs' => [],
                    'error' => 'Layanan rute jalan OSRM merespons dengan status error: ' . $response->status(),
                ];

                Cache::put($cacheKey, $failureResult, $this->failureCacheTtl);
                return $failureResult;
            }

            $data = $response->json();

            if (($data['code'] ?? '') !== 'Ok' || empty($data['routes'][0])) {
                $failureResult = [
                    'success' => false,
                    'distance_m' => null,
                    'duration_s' => null,
                    'geometry' => null,
                    'waypoints' => $waypoints,
                    'legs' => [],
                    'error' => 'OSRM tidak menemukan rute jalan yang menghubungkan titik-titik tersebut (' . ($data['message'] ?? 'NoRoute') . ').',
                ];

                Cache::put($cacheKey, $failureResult, $this->failureCacheTtl);
                return $failureResult;
            }

            $route = $data['routes'][0];
            $geometry = $route['geometry'] ?? null;

            if (! $this->isValidGeoJsonLineString($geometry)) {
                $failureResult = [
                    'success' => false,
                    'distance_m' => null,
                    'duration_s' => null,
                    'geometry' => null,
                    'waypoints' => $waypoints,
                    'legs' => [],
                    'error' => 'Geometri rute yang dikembalikan OSRM tidak valid.',
                ];

                Cache::put($cacheKey, $failureResult, $this->failureCacheTtl);
                return $failureResult;
            }

            // Extract per-leg geometry from steps for hybrid navigation
            $legsData = [];
            foreach ($route['legs'] ?? [] as $legIndex => $leg) {
                $legCoordinates = [];
                if (! empty($leg['steps'])) {
                    foreach ($leg['steps'] as $step) {
                        if (! empty($step['geometry']['coordinates'])) {
                            foreach ($step['geometry']['coordinates'] as $coord) {
                                if (empty($legCoordinates) || end($legCoordinates) !== $coord) {
                                    $legCoordinates[] = $coord;
                                }
                            }
                        }
                    }
                }

                $legsData[] = [
                    'leg_index' => $legIndex,
                    'distance_m' => (int) round($leg['distance'] ?? 0),
                    'duration_s' => (int) round($leg['duration'] ?? 0),
                    'geometry' => ! empty($legCoordinates) ? [
                        'type' => 'LineString',
                        'coordinates' => $legCoordinates,
                    ] : null,
                ];
            }

            $successResult = [
                'success' => true,
                'distance_m' => (int) round($route['distance'] ?? 0),
                'duration_s' => (int) round($route['duration'] ?? 0),
                'geometry' => $geometry,
                'waypoints' => $waypoints,
                'legs' => $legsData,
                'error' => null,
            ];

            Cache::put($cacheKey, $successResult, $this->cacheTtl);
            return $successResult;
        } catch (\Throwable $e) {
            Log::warning('OSRM routing request failed: ' . $e->getMessage());

            $failureResult = [
                'success' => false,
                'distance_m' => null,
                'duration_s' => null,
                'geometry' => null,
                'waypoints' => $waypoints,
                'legs' => [],
                'error' => 'Layanan rute jalan OSRM sedang tidak dapat dihubungi. Menampilkan titik tujuan tanpa geometri rute jalan.',
            ];

            Cache::put($cacheKey, $failureResult, $this->failureCacheTtl);
            return $failureResult;
        }
    }

    /**
     * Validate whether a geometry array is a valid GeoJSON LineString.
     */
    public function isValidGeoJsonLineString(mixed $geometry): bool
    {
        if (! is_array($geometry)) {
            return false;
        }

        if (($geometry['type'] ?? '') !== 'LineString') {
            return false;
        }

        if (! isset($geometry['coordinates']) || ! is_array($geometry['coordinates'])) {
            return false;
        }

        if (count($geometry['coordinates']) < 2) {
            return false;
        }

        foreach ($geometry['coordinates'] as $coord) {
            if (! is_array($coord) || count($coord) < 2) {
                return false;
            }
            if (! is_numeric($coord[0]) || ! is_numeric($coord[1])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate cache key for given waypoints.
     */
    public function getCacheKey(array $waypoints): string
    {
        $coordinatePairs = [];
        foreach ($waypoints as $point) {
            $lat = (float) ($point['lat'] ?? 0);
            $lng = (float) ($point['lng'] ?? 0);
            $coordinatePairs[] = sprintf('%.6f,%.6f', $lng, $lat);
        }

        $coordString = implode(';', $coordinatePairs);
        return 'osrm_route_' . md5("{$this->profile}_{$coordString}");
    }

    /**
     * Invalidate route cache for specific waypoints.
     */
    public function invalidateRouteCache(array $waypoints): void
    {
        Cache::forget($this->getCacheKey($waypoints));
    }

    /**
     * Invalidate cached route for a Trip.
     */
    public function invalidateTripRouteCache(Trip $trip): void
    {
        $trip->loadMissing(['sppg', 'stops.school']);
        $waypoints = $this->extractTripWaypoints($trip);
        if (count($waypoints) >= 2) {
            $this->invalidateRouteCache($waypoints);
        }
    }

    /**
     * Extract ordered waypoints from Trip.
     *
     * @return array<int, array{lat: float, lng: float, name: string, type: string, order: int, stop_id?: int}>
     */
    public function extractTripWaypoints(Trip $trip): array
    {
        $trip->loadMissing(['sppg', 'stops.school']);

        $sppg = $trip->sppg;
        if (! $sppg || ! $sppg->latitude || ! $sppg->longitude) {
            return [];
        }

        $waypoints = [
            [
                'lat' => (float) $sppg->latitude,
                'lng' => (float) $sppg->longitude,
                'name' => $sppg->name,
                'type' => 'SPPG',
                'order' => 0,
            ],
        ];

        $sortedStops = $trip->stops->sortBy('sequence_number');
        foreach ($sortedStops as $stop) {
            $school = $stop->school;
            if ($school && $school->latitude && $school->longitude) {
                $waypoints[] = [
                    'lat' => (float) $school->latitude,
                    'lng' => (float) $school->longitude,
                    'name' => $school->name,
                    'type' => 'SCHOOL',
                    'order' => $stop->sequence_number,
                    'stop_id' => $stop->id,
                ];
            }
        }

        return $waypoints;
    }

    /**
     * Calculate and cache route for a Trip model instance.
     */
    public function calculateTripRoute(Trip $trip): array
    {
        $waypoints = $this->extractTripWaypoints($trip);

        if (count($waypoints) < 2) {
            throw new InvalidArgumentException('Trip belum memiliki cukup koordinat GPS SPPG dan sekolah tujuan yang sah.');
        }

        $routeResult = $this->getRoute($waypoints);

        if ($routeResult['success'] && $this->isValidGeoJsonLineString($routeResult['geometry'])) {
            $trip->update([
                'route_geometry' => json_encode($routeResult['geometry']),
                'total_distance_m' => $routeResult['distance_m'],
                'estimated_duration_s' => $routeResult['duration_s'],
            ]);
        }

        return $routeResult;
    }
}
