<?php

declare(strict_types=1);

namespace App\Services
{
    function file_get_contents(string $url): string|false
    {
        return json_encode([
            'results' => [
                [
                    'geometry' => [
                        'location' => [
                            'lat' => 12.345,
                            'lng' => -67.89,
                        ],
                    ],
                ],
            ],
        ]);
    }
}

namespace Tests\Feature\Services
{

    use App\Services\GoogleService;
    use Illuminate\Foundation\Testing\DatabaseTransactions;
    use Tests\TestCase;

    class GoogleServiceTest extends TestCase
    {
        use DatabaseTransactions;

        public function test_get_latitude_longitude_returns_coordinates_from_google_response(): void
        {
            config(['services.google.maps_api_key' => 'fake-key']);

            $subject = new GoogleService;

            $result = $subject->getLatitudeLongitude('123 Main St');

            $this->assertSame([12.345, -67.89], $result);
        }
    }
}
