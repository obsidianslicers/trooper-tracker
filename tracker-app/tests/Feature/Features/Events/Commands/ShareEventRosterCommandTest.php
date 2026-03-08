<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Features\Events\Commands\ShareEventRosterCommand;
use App\Models\Event;
use App\Models\Trooper;
use Tests\TestCase;

/**
 * @see ShareEventRosterCommand
 */
class ShareEventRosterCommandTest extends TestCase
{
    public function test_constructor_stores_event_recipient_email_and_shared_by_trooper(): void
    {
        $event = new Event([Event::ID => 123]);
        $recipient_email = 'coordinator@example.com';
        $shared_by_trooper = new Trooper([Trooper::ID => 456]);

        $subject = new ShareEventRosterCommand(
            event: $event,
            recipient_email: $recipient_email,
            shared_by_trooper: $shared_by_trooper
        );

        $this->assertSame($event, $subject->event);
        $this->assertSame($recipient_email, $subject->recipient_email);
        $this->assertSame($shared_by_trooper, $subject->shared_by_trooper);
    }
}
