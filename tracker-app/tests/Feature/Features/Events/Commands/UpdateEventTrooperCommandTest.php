<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Features\Events\Commands\UpdateEventTrooperCommand;
use App\Models\EventTrooper;
use Tests\TestCase;

/**
 * @see UpdateEventTrooperCommand
 */
class UpdateEventTrooperCommandTest extends TestCase
{
    public function test_constructor_stores_event_trooper_and_valid_data(): void
    {
        $event_trooper = new EventTrooper([EventTrooper::ID => 123]);
        $valid_data = [EventTrooper::STATUS => 'attended', EventTrooper::IS_HANDLER => true];

        $subject = new UpdateEventTrooperCommand(
            event_trooper: $event_trooper,
            valid_data: $valid_data
        );

        $this->assertSame($event_trooper, $subject->event_trooper);
        $this->assertSame($valid_data, $subject->valid_data);
    }
}
