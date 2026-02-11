<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class MapControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('events.map'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_denies_pending_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asPending()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('events.map'));

        // Assert
        $response->assertUnauthorized();
    }

    public function test_invoke_displays_map_view_with_events_and_organizations(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $event_start = now()->addDays(2);
        $event_end = now()->addDays(2)->addHours(4);

        $event = Event::factory()->open()->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::EVENT_START => $event_start,
            Event::EVENT_END => $event_end,
            Event::LATITUDE => 33.4255,
            Event::LONGITUDE => -111.94,
        ]);

        // Act
        $response = $this->actingAs($trooper)->get(route('events.map'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.events.map');
        $response->assertViewHas('events');
        $response->assertViewHas('costume_organizations');

        $events = $response->viewData('events');
        $this->assertInstanceOf(Collection::class, $events);
        $this->assertTrue($events->contains(Event::ID, $event->id));

        $display_event = $events->firstWhere(Event::ID, $event->id);
        $this->assertNotNull($display_event);
        $this->assertNotNull($display_event->lat);
        $this->assertNotNull($display_event->lng);
        $this->assertNotNull($display_event->url);
        $this->assertNotNull($display_event->date_range);
    }

    public function test_invoke_filters_events_without_coordinates(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event_start = now()->addDays(3);
        $event_end = now()->addDays(3)->addHours(4);

        $visible_event = Event::factory()->open()->create([
            Event::EVENT_START => $event_start,
            Event::EVENT_END => $event_end,
            Event::LATITUDE => 40.7128,
            Event::LONGITUDE => -74.0060,
        ]);

        Event::factory()->open()->create([
            Event::EVENT_START => $event_start,
            Event::EVENT_END => $event_end,
            Event::LATITUDE => null,
            Event::LONGITUDE => null,
        ]);

        // Act
        $response = $this->actingAs($trooper)->get(route('events.map'));

        // Assert
        $response->assertOk();

        $events = $response->viewData('events');
        $this->assertInstanceOf(Collection::class, $events);
        $this->assertCount(1, $events);
        $this->assertTrue($events->contains(Event::ID, $visible_event->id));
    }
}
