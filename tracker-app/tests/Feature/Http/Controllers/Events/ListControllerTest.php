<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for ListController.
 *
 * Verifies:
 * - Authenticated troopers can view events list page
 * - Upcoming events are displayed
 * - Organizations are loaded
 */
class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_events_list_page(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('events.list'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.events.list');
    }

    public function test_invoke_passes_events_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        Event::factory()->create([
            Event::EVENT_START => now()->addWeek(),
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('events.list'));

        // Assert
        $response->assertViewHas('events');
    }

    public function test_invoke_passes_costume_organizations_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('events.list'));

        // Assert
        $response->assertViewHas('costume_organizations');
    }

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('events.list'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }
}
