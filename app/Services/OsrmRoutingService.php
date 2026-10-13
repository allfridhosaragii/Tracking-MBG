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

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.osrm.base_url', 'https://router.project-osrm.org'), '/');
        $this->profile = config('services.osrm.profile', 'driving');
        $this->timeout = (int) config('services.osrm.timeout', 4);
        $this->cacheTtl = (int) config('services.osrm.cache_ttl', 86400);
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
        $cacheKey = 'osrm_route_' . md5("{$this->profile}_{$coordString}");

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($coordString, $waypoints) {
            $url = "{$this->baseUrl}/route/v1/{$this->profile}/{$coordString}?overview=full&geometries=geojson&steps=false";

            try {
                $response = Http::timeout($this->timeout)
                    ->acceptJson()
                    ->get($url);

                if (! $response->successful()) {
                    Log::warning('OSRM request unsuccessful', [
                        'status' => $response->status(),
                        'url' => $url,
                    ]);

                    return [
                        'success' => false,
                        'distance_m' => null,
                        'duration_s' => null,
                        'geometry' => null,
                        'waypoints' => $waypoints,
                        'legs' => [],
                        'error' => 'Layanan rute jalan OSRM merespons dengan status error: ' . $response->status(),
                    ];
                }

                $data = $response->json();

                if (($data['code'] ?? '') !== 'Ok' || empty($data['routes'][0])) {
                    return [
                        'success' => false,
                        'distance_m' => null,
                        'duration_s' => null,
                        'geometry' => null,
                        'waypoints' => $waypoints,
                        'legs' => [],
                        'error' => 'OSRM tidak menemukan rute jalan yang menghubungkan titik-titik tersebut (' . ($data['message'] ?? 'NoRoute') . ').',
                    ];
                }

                $route = $data['routes'][0];

                return [
                    'success' => true,
                    'distance_m' => (int) round($route['distance'] ?? 0),
                    'duration_s' => (int) round($route['duration'] ?? 0),
                    'geometry' => $route['geometry'] ?? null,
                    'waypoints' => $waypoints,
                    'legs' => $route['legs'] ?? [],
                    'error' => null,
                ];
            } catch (\Throwable $e) {
                Log::warning('OSRM routing request failed: ' . $e->getMessage());

                return [
                    'success' => false,
                    'distance_m' => null,
                    'duration_s' => null,
                    'geometry' => null,
                    'waypoints' => $waypoints,
                    'legs' => [],
                    'error' => 'Layanan rute jalan OSRM sedang tidak dapat dihubungi. Menampilkan titik tujuan tanpa geometri rute jalan.',
                ];
            }
        });
    }

    /**
     * Calculate and cache route for a Trip model instance.
     */
    public function calculateTripRoute(Trip $trip): array
    {
        $trip->loadMissing(['sppg', 'stops.school']);

        $sppg = $trip->sppg;
        if (! $sppg || ! $sppg->latitude || ! $sppg->longitude) {
            throw new InvalidArgumentException('SPPG asal (' . ($sppg?->name ?? 'N/A') . ') belum memiliki koordinat GPS yang sah.');
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
            if (! $school || ! $school->latitude || ! $school->longitude) {
                throw new InvalidArgumentException("Sekolah tujuan {$school?->name} (Stop #{$stop->sequence_number}) belum memiliki koordinat GPS yang sah.");
            }

            $waypoints[] = [
                'lat' => (float) $school->latitude,
                'lng' => (float) $school->longitude,
                'name' => $school->name,
                'type' => 'SCHOOL',
                'order' => $stop->sequence_number,
                'stop_id' => $stop->id,
            ];
        }

        $routeResult = $this->getRoute($waypoints);

        if ($routeResult['success'] && ! empty($routeResult['geometry'])) {
            $trip->update([
                'route_geometry' => json_encode($routeResult['geometry']),
                'total_distance_m' => $routeResult['distance_m'],
                'estimated_duration_s' => $routeResult['duration_s'],
            ]);
        }

        return $routeResult;
    }
}
