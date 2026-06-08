<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Features\Events\Commands\SendTentativeReminderCommand;
use App\Features\Events\Commands\SendTentativeReminderCommandHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Notifications\Events\TentativeStatusReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendTentativeReminderCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_sends_notification_to_trooper(): void
    {
        Notification::fake();

        $event = Event::factory()->withEventStart(now()->addDays(3))->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->asMember()->create();
        $event_trooper = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asTentative()->create();

        $handler = app(SendTentativeReminderCommandHandler::class);
        $handler(new SendTentativeReminderCommand($event_trooper));

        Notification::assertSentTo($trooper, TentativeStatusReminderNotification::class);
    }
}
