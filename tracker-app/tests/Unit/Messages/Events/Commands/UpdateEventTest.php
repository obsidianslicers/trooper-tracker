<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Messages\Events\Commands\UpdateEvent;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_updates_event_with_valid_data(): void
    {
        $event = Event::factory()->create([
            Event::NAME => 'Original Name',
            Event::VENUE => 'Original Venue',
        ]);

        $data = [
            Event::NAME => 'Updated Name',
            Event::VENUE => 'Updated Venue',
        ];

        $command = new UpdateEvent(
            event: $event,
            data: $data
        );

        $command->handle();

        $this->assertDatabaseHas('tt_events', [
            Event::ID => $event->id,
            Event::NAME => 'Updated Name',
            Event::VENUE => 'Updated Venue',
        ]);
    }

    public function test_handle_persists_changes_to_database(): void
    {
        $event = Event::factory()->create([Event::NAME => 'Old Name']);

        $command = new UpdateEvent(
            event: $event,
            data: [Event::NAME => 'New Name']
        );

        $command->handle();

        $event->refresh();

        $this->assertSame('New Name', $event->{Event::NAME});
    }

    public function test_handle_returns_null(): void
    {
        $event = Event::factory()->create();

        $command = new UpdateEvent(
            event: $event,
            data: [Event::NAME => 'Test']
        );

        $result = $command->handle();

        $this->assertNull($result);
    }
}
