<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\GeocodingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeocodingServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_get_latitude_longitude_returns_coordinates_on_success(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([
                ['lat' => '40.7128', 'lon' => '-74.0060'],
            ], 200),
        ]);

        $subject = new GeocodingService;

        $result = $subject->getLatitudeLongitude('New York, NY');

        $this->assertSame([40.7128, -74.0060], $result);
    }

    public function test_get_latitude_longitude_returns_null_on_unsuccessful_response(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([], 500),
        ]);

        $subject = new GeocodingService;

        $this->assertNull($subject->getLatitudeLongitude('Bad'));
    }

    public function test_get_latitude_longitude_returns_null_when_no_rows_found(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([], 200),
        ]);

        $subject = new GeocodingService;

        $this->assertNull($subject->getLatitudeLongitude('Missing'));
    }
}
