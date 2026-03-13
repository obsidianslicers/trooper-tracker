<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Features\Events\Commands\PromoteNextInLineEventTrooperCommand;
use App\Models\EventTrooper;
use Tests\TestCase;

/**
 * @see PromoteNextInLineEventTrooperCommand
 */
class PromoteNextInLineEventTrooperCommandTest extends TestCase
{
    public function test_constructor_stores_event_trooper(): void
    {
        $event_trooper = new EventTrooper([EventTrooper::ID => 123]);

        $subject = new PromoteNextInLineEventTrooperCommand(event_trooper: $event_trooper);

        $this->assertSame($event_trooper, $subject->event_trooper);
    }
}
