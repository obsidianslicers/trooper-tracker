<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeocodingService
{
    public function getLatitudeLongitude(string $query): ?array
    {
        $response = Http::get('https://nominatim.openstreetmap.org/search', [
            'q' => $query,
            'format' => 'json',
            'limit' => 1,
        ]);

        if (!$response->successful() || empty($response[0]))
        {
            return null;
        }

        $latitude = (float) $response[0]['lat'];
        $longitude = (float) $response[0]['lon'];

        return [$latitude, $longitude];
    }
}
