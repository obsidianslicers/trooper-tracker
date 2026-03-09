<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Features\Events\Commands\SendEventCancelledNotificationCommand;
use App\Models\Event;
use App\Models\Trooper;
use Tests\TestCase;

/**
 * @see SendEventCancelledNotificationCommand
 */
class SendEventCancelledNotificationCommandTest extends TestCase
{
    public function test_constructor_stores_event_and_trooper(): void
    {
        $event = new Event([Event::ID => 123]);
        $trooper = new Trooper([Trooper::ID => 456]);

        $subject = new SendEventCancelledNotificationCommand(
            event: $event,
            trooper: $trooper
        );

        $this->assertSame($event, $subject->event);
        $this->assertSame($trooper, $subject->trooper);
    }
}
