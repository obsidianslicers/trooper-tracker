<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Features\Events\Commands\UpdateEventTrooperCommand;
use App\Features\Events\Commands\UpdateEventTrooperCommandHandler;
use App\Models\EventTrooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see UpdateEventTrooperCommandHandler
 */
class UpdateEventTrooperCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_event_trooper_with_valid_data(): void
    {
        $event_trooper = EventTrooper::factory()->create([
            EventTrooper::STATUS => EventTrooperStatus::TENTATIVE,
            EventTrooper::IS_HANDLER => false,
        ]);

        $valid_data = [
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
            EventTrooper::IS_HANDLER => true,
        ];

        $command = new UpdateEventTrooperCommand(
            event_trooper: $event_trooper,
            valid_data: $valid_data
        );
        $handler = app(UpdateEventTrooperCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::ID => $event_trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED->value,
            EventTrooper::IS_HANDLER => true,
        ]);
    }

    public function test_invoke_persists_changes_to_database(): void
    {
        $event_trooper = EventTrooper::factory()->create([
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $command = new UpdateEventTrooperCommand(
            event_trooper: $event_trooper,
            valid_data: [EventTrooper::STATUS => EventTrooperStatus::CANCELLED]
        );
        $handler = app(UpdateEventTrooperCommandHandler::class);

        $handler($command);

        $event_trooper->refresh();

        $this->assertSame(EventTrooperStatus::CANCELLED, $event_trooper->{EventTrooper::STATUS});
    }

    public function test_invoke_returns_null(): void
    {
        $event_trooper = EventTrooper::factory()->create();

        $command = new UpdateEventTrooperCommand(
            event_trooper: $event_trooper,
            valid_data: [EventTrooper::IS_HANDLER => true]
        );
        $handler = app(UpdateEventTrooperCommandHandler::class);

        $result = $handler($command);

        $this->assertNull($result);
    }
}
