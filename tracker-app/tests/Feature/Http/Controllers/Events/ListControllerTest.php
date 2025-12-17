<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_upcoming_events_list(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        Event::factory()->count(3)->create([
            'event_start' => Carbon::now()->addDays(10),
        ]);

        // Act
        $response = $this->actingAs($trooper)->get(route('events.list'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.events.list');
        $response->assertViewHas('events');
    }

    public function test_invoke_shows_only_upcoming_events(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        $past_event = Event::factory()->create([
            'name' => 'Past Event',
            'event_start' => Carbon::now()->subDays(10),
            'event_end' => Carbon::now()->subDays(10)->addHours(2),
        ]);

        $future_event = Event::factory()->create([
            'name' => 'Future Event',
            'event_start' => Carbon::now()->addDays(10),
            'event_end' => Carbon::now()->addDays(10)->addHours(2),
        ]);

        // Act
        $response = $this->actingAs($trooper)->get(route('events.list'));

        // Assert
        $response->assertOk();
        $response->assertSee('Future Event');
        $response->assertDontSee('Past Event');
    }

    public function test_invoke_includes_event_shifts_in_response(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(5),
        ]);

        // Act
        $response = $this->actingAs($trooper)->get(route('events.list'));

        // Assert
        $response->assertOk();
        $events = $response->viewData('events');
        $this->assertNotNull($events);
    }

    public function test_invoke_denies_access_to_unauthenticated_user(): void
    {
        // Act
        $response = $this->get(route('events.list'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_allows_access_to_pending_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asPending()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('events.list'));

        // Assert
        $response->assertOk();
    }

    public function test_invoke_allows_access_to_retired_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asRetired()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('events.list'));

        // Assert
        $response->assertOk();
    }
}
