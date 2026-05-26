<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OsrmRoutingService
{
    public function __construct(
        private readonly string $baseUrl = '',
    ) {}

    public function route(float $sourceLat, float $sourceLng, float $targetLat, float $targetLng): array
    {
        $base = $this->baseUrl ?: (string) config('services.osrm.base_url', env('OSRM_BASE_URL', 'http://127.0.0.1:5000'));
        $base = rtrim($base, '/');

        $url = sprintf(
            '%s/route/v1/driving/%s,%s;%s,%s',
            $base,
            $sourceLng,
            $sourceLat,
            $targetLng,
            $targetLat
        );

        $response = Http::timeout(8)
            ->acceptJson()
            ->get($url, [
                'overview' => 'full',
                'geometries' => 'geojson',
                'annotations' => 'false',
                'steps' => 'false',
            ]);

        if (! $response->ok()) {
            throw new \RuntimeException('OSRM error: HTTP '.$response->status());
        }

        $payload = $response->json();
        $route = $payload['routes'][0] ?? null;
        $geometry = $route['geometry']['coordinates'] ?? null;

        if (! is_array($geometry) || empty($geometry)) {
            throw new \RuntimeException('OSRM error: missing geometry.');
        }

        $points = [];
        foreach ($geometry as $pair) {
            if (! is_array($pair) || count($pair) < 2) {
                continue;
            }
            $lng = (float) $pair[0];
            $lat = (float) $pair[1];
            $points[] = [$lat, $lng];
        }

        if (count($points) < 2) {
            throw new \RuntimeException('OSRM error: geometry too short.');
        }

        return [
            'geometry' => $points,
            'distance_meters' => isset($route['distance']) ? (float) $route['distance'] : null,
            'duration_seconds' => isset($route['duration']) ? (float) $route['duration'] : null,
        ];
    }
}

