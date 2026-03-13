<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Features\Events\Commands\UpdateEventCommand;
use App\Features\Events\Commands\UpdateEventCommandHandler;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see UpdateEventCommandHandler
 */
class UpdateEventCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_event_with_valid_data(): void
    {
        $event = Event::factory()->create([
            Event::NAME => 'Original Name',
            Event::VENUE => 'Original Venue',
        ]);

        $data = [
            Event::NAME => 'Updated Name',
            Event::VENUE => 'Updated Venue',
        ];

        $command = new UpdateEventCommand(
            event: $event,
            data: $data
        );
        $handler = app(UpdateEventCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_events', [
            Event::ID => $event->id,
            Event::NAME => 'Updated Name',
            Event::VENUE => 'Updated Venue',
        ]);
    }

    public function test_invoke_persists_changes_to_database(): void
    {
        $event = Event::factory()->create([Event::NAME => 'Old Name']);

        $command = new UpdateEventCommand(
            event: $event,
            data: [Event::NAME => 'New Name']
        );
        $handler = app(UpdateEventCommandHandler::class);

        $handler($command);

        $event->refresh();

        $this->assertSame('New Name', $event->{Event::NAME});
    }

    public function test_invoke_returns_null(): void
    {
        $event = Event::factory()->create();

        $command = new UpdateEventCommand(
            event: $event,
            data: [Event::NAME => 'Test']
        );
        $handler = app(UpdateEventCommandHandler::class);

        $result = $handler($command);

        $this->assertNull($result);
    }
}
