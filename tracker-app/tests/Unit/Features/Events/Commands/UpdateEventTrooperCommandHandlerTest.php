<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Features\Events\Commands\UpdateEventTrooperCommand;
use App\Features\Events\Commands\UpdateEventTrooperCommandHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for UpdateEventTrooperCommandHandler.
 *
 * Verifies:
 * - Updates event trooper attributes
 * - Persists changes to database
 * - Returns null
 */
class UpdateEventTrooperCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_event_trooper_attributes(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->create();

        $event_trooper = EventTrooper::factory()
            ->for($shift, 'event_shift')
            ->for($trooper, 'trooper')
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::GOING,
            ]);

        $command = new UpdateEventTrooperCommand(
            $event_trooper,
            [EventTrooper::STATUS => EventTrooperStatus::STAND_BY]
        );
        $subject = new UpdateEventTrooperCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $event_trooper->refresh();
        $this->assertEquals(EventTrooperStatus::STAND_BY, $event_trooper->status);
    }

    public function test_invoke_persists_changes_to_database(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->create();

        $event_trooper = EventTrooper::factory()
            ->for($shift, 'event_shift')
            ->for($trooper, 'trooper')
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::GOING,
            ]);

        $command = new UpdateEventTrooperCommand(
            $event_trooper,
            [EventTrooper::STATUS => EventTrooperStatus::STAND_BY]
        );
        $subject = new UpdateEventTrooperCommandHandler();

        // Act
        $subject($command);

        // Assert
        $this->assertDatabaseHas('tt_event_troopers', [
            'id' => $event_trooper->id,
            'status' => EventTrooperStatus::STAND_BY->value,
        ]);
    }

    public function test_invoke_returns_null(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->create();

        $event_trooper = EventTrooper::factory()
            ->for($shift, 'event_shift')
            ->for($trooper, 'trooper')
            ->create();

        $command = new UpdateEventTrooperCommand($event_trooper, []);
        $subject = new UpdateEventTrooperCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertNull($result);
    }
}
