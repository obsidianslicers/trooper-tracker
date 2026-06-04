<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\RosterAction;
use App\Jobs\SendEventRosterActivityNotificationsJob;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\EventWatch;
use App\Models\Trooper;
use App\Notifications\Admin\EventRosterActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendEventRosterActivityNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_notifies_troopers_watching_the_event(): void
    {
        Notification::fake();

        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $signed_up_trooper = Trooper::factory()->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($signed_up_trooper)
            ->create();

        $watcher = Trooper::factory()->create();
        EventWatch::factory()->create([
            EventWatch::EVENT_ID => $event->id,
            EventWatch::TROOPER_ID => $watcher->id,
        ]);

        $subject = new SendEventRosterActivityNotificationsJob($event_trooper, RosterAction::SIGNED_UP);
        $subject->handle();

        Notification::assertSentTo($watcher, EventRosterActivityNotification::class);
    }

    public function test_handle_does_not_notify_the_trooper_who_signed_up(): void
    {
        Notification::fake();

        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $signed_up_trooper = Trooper::factory()->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($signed_up_trooper)
            ->create();

        EventWatch::factory()->create([
            EventWatch::EVENT_ID => $event->id,
            EventWatch::TROOPER_ID => $signed_up_trooper->id,
        ]);

        $subject = new SendEventRosterActivityNotificationsJob($event_trooper, RosterAction::SIGNED_UP);
        $subject->handle();

        Notification::assertNotSentTo($signed_up_trooper, EventRosterActivityNotification::class);
    }

    public function test_handle_notifies_multiple_watchers(): void
    {
        Notification::fake();

        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()->forEventShift($event_shift)->create();

        $watcher_one = Trooper::factory()->create();
        $watcher_two = Trooper::factory()->create();

        EventWatch::factory()->create([EventWatch::EVENT_ID => $event->id, EventWatch::TROOPER_ID => $watcher_one->id]);
        EventWatch::factory()->create([EventWatch::EVENT_ID => $event->id, EventWatch::TROOPER_ID => $watcher_two->id]);

        $subject = new SendEventRosterActivityNotificationsJob($event_trooper, RosterAction::SIGNED_UP);
        $subject->handle();

        Notification::assertSentTo($watcher_one, EventRosterActivityNotification::class);
        Notification::assertSentTo($watcher_two, EventRosterActivityNotification::class);
    }

    public function test_handle_sends_no_notifications_when_no_watchers(): void
    {
        Notification::fake();

        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()->forEventShift($event_shift)->create();

        $subject = new SendEventRosterActivityNotificationsJob($event_trooper, RosterAction::SIGNED_UP);
        $subject->handle();

        Notification::assertNothingSent();
    }
}
