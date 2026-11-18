<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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
            'list' => ['events.list', 'events/List'],
            'calendar' => ['events.calendar', 'events/Calendar'],
            'completed' => ['events.closed', 'events/PastEvents'],
            'cancelled' => ['events.cancelled', 'events/PastEvents'],
        ];
    }

    #[DataProvider('pages')]
    public function test_each_svelte_events_page_knows_whether_to_offer_the_map(string $route, string $component): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        config(['services.google.maps_api_key' => 'test-key']);
        $this->actingAs($trooper)->get(route($route))
            ->assertInertia(fn (Assert $page) => $page->component($component)->where('has_map', true));

        config(['services.google.maps_api_key' => null]);
        $this->actingAs($trooper)->get(route($route))
            ->assertInertia(fn (Assert $page) => $page->where('has_map', false));
    }

    public function test_the_map_page_links_back_to_the_list(): void
    {
        config(['services.google.maps_api_key' => 'test-key']);
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->get(route('events.map'));

        $response->assertOk();
        $response->assertSee('class="d-flex flex-wrap justify-content-center gap-2 mb-3 event-views"', false);
        $response->assertSee('href="'.route('events.list').'"', false);
        $response->assertSee('href="'.route('events.calendar').'"', false);
        $response->assertSeeInOrder(['aria-current="page"', 'Map'], false);
    }
}
