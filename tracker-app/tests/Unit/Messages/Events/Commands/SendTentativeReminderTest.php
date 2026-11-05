<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Messages\Events\Commands\SendTentativeReminder;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Notifications\Events\TentativeStatusReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendTentativeReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sends_notification_to_trooper(): void
    {
        Notification::fake();

        $event = Event::factory()->withEventStart(now()->addDays(3))->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->asMember()->create();
        $event_trooper = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asTentative()->create();

        (new SendTentativeReminder($event_trooper))->handle();

        Notification::assertSentTo(
            $trooper,
            TentativeStatusReminderNotification::class,
            fn (TentativeStatusReminderNotification $notification): bool => $notification->toArray($trooper)['url'] === '/events/details/'.$event->id,
        );
    }
}
