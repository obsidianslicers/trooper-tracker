<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for CalendarController.
 *
 * Verifies:
 * - Authenticated troopers can view calendar page
 * - Events are grouped by date
 * - Calendar months are generated
 */
class CalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_calendar_page(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('events.calendar'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.events.calendar');
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
            ->get(route('events.calendar'));

        // Assert
        $response->assertViewHas('events');
    }

    public function test_invoke_passes_months_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('events.calendar'));

        // Assert
        $response->assertViewHas('months');
        $months = $response->viewData('months');
        $this->assertCount(12, $months);
    }

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('events.calendar'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }
}
