<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Bus\MagicBus;
use App\Enums\EventStatus;
use App\Features\Events\Queries\GetEventsToCloseQuery;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Test suite for CloseEventsCommand.
 *
 * Verifies that the command correctly identifies and closes events
 * whose end date has passed by updating their status to CLOSED.
 */
class CloseEventsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the command closes events returned by the query.
     */
    public function test_command_closes_events_returned_by_query(): void
    {
        // Arrange: Create test events
        $event1 = Event::factory()->create([Event::STATUS => EventStatus::OPEN]);
        $event2 = Event::factory()->create([Event::STATUS => EventStatus::OPEN]);
        $events = collect([$event1, $event2]);

        // Mock MagicBus to return events to close
        $this->mock(MagicBus::class, function (MockInterface $mock) use ($events)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetEventsToCloseQuery::class))
                ->andReturn($events);
        });

        // Act: Execute the command
        $this->artisan('tracker:close-events')
            ->assertExitCode(0);

        // Assert: Verify events are closed
        $event1->refresh();
        $event2->refresh();
        $this->assertEquals(EventStatus::CLOSED, $event1->status);
        $this->assertEquals(EventStatus::CLOSED, $event2->status);
    }

    /**
     * Test that the command handles empty event collection gracefully.
     */
    public function test_command_handles_no_events_to_close(): void
    {
        // Arrange: Mock MagicBus to return empty collection
        $this->mock(MagicBus::class, function (MockInterface $mock)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetEventsToCloseQuery::class))
                ->andReturn(collect([]));
        });

        // Act & Assert: Command should complete successfully
        $this->artisan('tracker:close-events')
            ->assertExitCode(0);
    }

    /**
     * Test that the command does not close events not returned by query.
     */
    public function test_command_does_not_close_events_not_in_query_results(): void
    {
        // Arrange: Create events - one to close, one to remain open
        $event_to_close = Event::factory()->create([Event::STATUS => EventStatus::OPEN]);
        $event_to_remain_open = Event::factory()->create([Event::STATUS => EventStatus::OPEN]);

        // Mock MagicBus to return only one event
        $this->mock(MagicBus::class, function (MockInterface $mock) use ($event_to_close)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetEventsToCloseQuery::class))
                ->andReturn(collect([$event_to_close]));
        });

        // Act: Execute the command
        $this->artisan('tracker:close-events')
            ->assertExitCode(0);

        // Assert: Only specified event is closed
        $event_to_close->refresh();
        $event_to_remain_open->refresh();
        $this->assertEquals(EventStatus::CLOSED, $event_to_close->status);
        $this->assertEquals(EventStatus::OPEN, $event_to_remain_open->status);
    }

    /**
     * Test that the command dispatches the correct query through the bus.
     */
    public function test_command_dispatches_get_events_to_close_query(): void
    {
        // Arrange & Assert: Verify correct query is sent
        $this->mock(MagicBus::class, function (MockInterface $mock)
        {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function ($query)
                {
                    return $query instanceof GetEventsToCloseQuery;
                })
                ->andReturn(collect([]));
        });

        // Act: Execute the command
        $this->artisan('tracker:close-events')
            ->assertExitCode(0);
    }

    /**
     * Test that the command has the correct signature.
     */
    public function test_command_has_correct_signature(): void
    {
        // Act: Get all registered commands
        $commands = $this->app['Illuminate\Contracts\Console\Kernel']->all();

        // Assert: Verify command exists
        $this->assertArrayHasKey('tracker:close-events', $commands);
    }

    /**
     * Test that command closes multiple events in a single execution.
     */
    public function test_command_closes_multiple_events_in_batch(): void
    {
        // Arrange: Create multiple events with different statuses
        $events = Event::factory()
            ->count(5)
            ->create([Event::STATUS => EventStatus::OPEN]);

        // Mock MagicBus to return all events
        $this->mock(MagicBus::class, function (MockInterface $mock) use ($events)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetEventsToCloseQuery::class))
                ->andReturn($events);
        });

        // Act: Execute the command
        $this->artisan('tracker:close-events')
            ->assertExitCode(0);

        // Assert: All events are closed
        foreach ($events as $event)
        {
            $event->refresh();
            $this->assertEquals(EventStatus::CLOSED, $event->status);
        }
    }
}
