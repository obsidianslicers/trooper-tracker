<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Messages\Events\Commands\UpdateEventTrooper;
use App\Models\EventTrooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateEventTrooperTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_updates_event_trooper_with_valid_data(): void
    {
        $event_trooper = EventTrooper::factory()->create([
            EventTrooper::STATUS => EventTrooperStatus::TENTATIVE,
            EventTrooper::IS_HANDLER => false,
        ]);

        $valid_data = [
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
            EventTrooper::IS_HANDLER => true,
        ];

        $command = new UpdateEventTrooper(
            event_trooper: $event_trooper,
            valid_data: $valid_data
        );

        $command->handle();

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::ID => $event_trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED->value,
            EventTrooper::IS_HANDLER => true,
        ]);
    }

    public function test_handle_persists_changes_to_database(): void
    {
        $event_trooper = EventTrooper::factory()->create([
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $command = new UpdateEventTrooper(
            event_trooper: $event_trooper,
            valid_data: [EventTrooper::STATUS => EventTrooperStatus::CANCELLED]
        );

        $command->handle();

        $event_trooper->refresh();

        $this->assertSame(EventTrooperStatus::CANCELLED, $event_trooper->{EventTrooper::STATUS});
    }

    public function test_handle_returns_null(): void
    {
        $event_trooper = EventTrooper::factory()->create();

        $command = new UpdateEventTrooper(
            event_trooper: $event_trooper,
            valid_data: [EventTrooper::IS_HANDLER => true]
        );

        $result = $command->handle();

        $this->assertNull($result);
    }
}
