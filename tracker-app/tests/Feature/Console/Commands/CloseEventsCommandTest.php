<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Services\Events\GetEventsToCloseQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CloseEventsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_closes_events_that_have_ended(): void
    {
        // Arrange
        $ended_event = Event::factory()->create([
            'status' => EventStatus::OPEN,
            'event_end' => Carbon::yesterday(),
        ]);

        // Act
        $this->artisan('tracker:close-events')->assertExitCode(0);

        // Assert
        $this->assertEquals(EventStatus::CLOSED, $ended_event->fresh()->status);
    }

    public function test_it_closes_multiple_events_that_have_ended(): void
    {
        // Arrange
        $ended_event1 = Event::factory()->create([
            'status' => EventStatus::OPEN,
            'event_end' => Carbon::yesterday(),
        ]);

        $ended_event2 = Event::factory()->create([
            'status' => EventStatus::DRAFT,
            'event_end' => Carbon::parse('-2 days'),
        ]);

        $ended_event3 = Event::factory()->create([
            'status' => EventStatus::SIGN_UP_LOCKED,
            'event_end' => Carbon::parse('-1 week'),
        ]);

        // Act
        $this->artisan('tracker:close-events')->assertExitCode(0);

        // Assert
        $this->assertEquals(EventStatus::CLOSED, $ended_event1->fresh()->status);
        $this->assertEquals(EventStatus::CLOSED, $ended_event2->fresh()->status);
        $this->assertEquals(EventStatus::CLOSED, $ended_event3->fresh()->status);
    }

    public function test_it_does_not_close_events_that_have_not_ended(): void
    {
        // Arrange
        $future_event = Event::factory()->create([
            'status' => EventStatus::OPEN,
            'event_end' => Carbon::tomorrow(),
        ]);

        // Act
        $this->artisan('tracker:close-events')->assertExitCode(0);

        // Assert
        $this->assertEquals(EventStatus::OPEN, $future_event->fresh()->status);
    }

    public function test_it_does_not_affect_already_closed_events(): void
    {
        // Arrange
        $closed_event = Event::factory()->create([
            'status' => EventStatus::CLOSED,
            'event_end' => Carbon::yesterday(),
        ]);

        // Act
        $this->artisan('tracker:close-events')->assertExitCode(0);

        // Assert
        $this->assertEquals(EventStatus::CLOSED, $closed_event->fresh()->status);
    }

    public function test_it_does_not_affect_cancelled_events(): void
    {
        // Arrange
        $cancelled_event = Event::factory()->create([
            'status' => EventStatus::CANCELLED,
            'event_end' => Carbon::yesterday(),
        ]);

        // Act
        $this->artisan('tracker:close-events')->assertExitCode(0);

        // Assert
        $this->assertEquals(EventStatus::CANCELLED, $cancelled_event->fresh()->status);
    }

    public function test_it_handles_no_events_to_close_gracefully(): void
    {
        // Arrange - only future events
        Event::factory()->count(3)->create([
            'status' => EventStatus::OPEN,
            'event_end' => Carbon::tomorrow(),
        ]);

        // Act & Assert - should complete without errors
        $this->artisan('tracker:close-events')->assertExitCode(0);
    }

    public function test_it_uses_get_events_to_close_query_service(): void
    {
        // Arrange
        $ended_event = Event::factory()->create([
            'status' => EventStatus::OPEN,
            'event_end' => Carbon::yesterday(),
        ]);

        $service = app(GetEventsToCloseQuery::class);
        $events_before = $service();

        // Act
        $this->artisan('tracker:close-events')->assertExitCode(0);

        // Assert - verify service would no longer return this event
        $events_after = $service();
        $this->assertCount(1, $events_before);
        $this->assertCount(0, $events_after);
    }
}
