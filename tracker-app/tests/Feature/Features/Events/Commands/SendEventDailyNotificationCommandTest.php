<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Features\Events\Commands\SendEventDailyNotificationCommand;
use App\Models\Trooper;
use Tests\TestCase;

/**
 * @see SendEventDailyNotificationCommand
 */
class SendEventDailyNotificationCommandTest extends TestCase
{
    public function test_constructor_stores_trooper(): void
    {
        $trooper = new Trooper([Trooper::ID => 456]);

        $subject = new SendEventDailyNotificationCommand(trooper: $trooper);

        $this->assertSame($trooper, $subject->trooper);
    }
}
