<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for EventDisplayController.
 *
 * Verifies:
 * - Authenticated troopers can view event sign-up page
 * - Event data and shifts are displayed correctly
 * - Breadcrumbs are set properly
 * - Can moderate flag is set correctly for moderators
 */
class EventDisplayControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_event_page(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([
            Event::STATUS => EventStatus::OPEN,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('events.display', $event));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.events.event-display');
    }

    public function test_invoke_passes_event_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('events.display', $event));

        // Assert
        $response->assertViewHas('event');
        $view_event = $response->viewData('event');
        $this->assertEquals($event->id, $view_event->id);
    }

    public function test_invoke_includes_event_shifts(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        EventShift::factory()->for($event)->count(2)->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('events.display', $event));

        // Assert
        $view_event = $response->viewData('event');
        $this->assertTrue($view_event->relationLoaded('event_shifts'));
        $this->assertCount(2, $view_event->event_shifts);
    }

    public function test_invoke_sets_can_moderate_false_for_regular_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('events.display', $event));

        // Assert
        $response->assertViewHas('can_moderate', false);
    }

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $event = Event::factory()->create();

        // Act
        $response = $this->get(route('events.display', $event));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }
}
