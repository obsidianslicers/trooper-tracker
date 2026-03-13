<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Features\Events\Commands\SendEventCreatedNotificationCommand;
use App\Models\Event;
use App\Models\Trooper;
use Tests\TestCase;

/**
 * @see SendEventCreatedNotificationCommand
 */
class SendEventCreatedNotificationCommandTest extends TestCase
{
    public function test_constructor_stores_event_and_trooper(): void
    {
        $event = new Event([Event::ID => 123]);
        $trooper = new Trooper([Trooper::ID => 456]);

        $subject = new SendEventCreatedNotificationCommand(
            event: $event,
            trooper: $trooper
        );

        $this->assertSame($event, $subject->event);
        $this->assertSame($trooper, $subject->trooper);
    }
}
