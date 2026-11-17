<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EventViewsNavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function pages(): array
    {
        return [
            'calendar' => ['events.calendar', 'Calendar'],
            'map' => ['events.map', 'Map'],
            'completed' => ['events.closed', 'Completed'],
            'cancelled' => ['events.cancelled', 'Cancelled'],
        ];
    }

    #[DataProvider('pages')]
    public function test_each_events_page_links_back_to_the_list(string $route, string $label): void
    {
        config(['services.google.maps_api_key' => 'test-key']);
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->get(route($route));

        $response->assertOk();
        $response->assertSee('class="d-flex flex-wrap justify-content-center gap-2 mb-3 event-views"', false);
        $response->assertSee('href="'.route('events.list').'"', false);
        $response->assertSeeInOrder(['aria-current="page"', $label], false);
    }

    public function test_the_map_link_is_left_out_without_a_maps_key(): void
    {
        config(['services.google.maps_api_key' => null]);
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->get(route('events.closed'));

        $response->assertDontSee('href="'.route('events.map').'"', false);
    }
}
