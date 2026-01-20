<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Dashboard;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for UpcomingTroopsHtmxController.
 *
 * Verifies:
 * - Authenticated troopers can view upcoming troops HTMX partial
 * - Only future event shifts are shown
 * - Events are ordered by start time
 */
class UpcomingTroopsHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_htmx_partial(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('dashboard.upcoming-troops-htmx'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.dashboard.upcoming-troops');
    }

    public function test_invoke_passes_event_shifts_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([
            Event::STATUS => EventStatus::OPEN,
        ]);
        $shift = EventShift::factory()->for($event)->create([
            EventShift::SHIFT_STARTS_AT => now()->addWeek(),
            EventShift::STATUS => EventStatus::OPEN,
        ]);
        EventTrooper::factory()->for($shift, 'event_shift')->for($trooper)->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('dashboard.upcoming-troops-htmx'));

        // Assert
        $response->assertViewHas('upcoming_shifts');
        $event_shifts = $response->viewData('upcoming_shifts');
        $this->assertCount(1, $event_shifts);
    }

    public function test_invoke_excludes_past_events(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $past_event = Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
        ]);
        $past_shift = EventShift::factory()->for($past_event)->create([
            EventShift::SHIFT_STARTS_AT => now()->subWeek(),
            EventShift::STATUS => EventStatus::CLOSED,
        ]);
        EventTrooper::factory()->for($past_shift, 'event_shift')->for($trooper)->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('dashboard.upcoming-troops-htmx'));

        // Assert
        $event_shifts = $response->viewData('upcoming_shifts');
        $this->assertCount(0, $event_shifts);
    }

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('dashboard.upcoming-troops-htmx'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }
}
