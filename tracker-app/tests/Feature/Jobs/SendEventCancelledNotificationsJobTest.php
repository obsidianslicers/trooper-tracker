<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Jobs\SendEventCancelledNotificationsJob;
use App\Messages\Events\Commands\SendEventCancelledNotification;
use App\Messages\Events\Queries\GetTroopersForEventCancelled;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class SendEventCancelledNotificationsJobTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_sends_commands_and_sets_cancelled_notification_timestamp(): void
    {
        $event = Event::factory()->create();
        $shift_one = EventShift::factory()->forEvent($event)->create();
        $shift_two = EventShift::factory()->forEvent($event)->asClosed()->create();

        $event_trooper_one = EventTrooper::factory()
            ->forEventShift($shift_one)
            ->forTrooper(Trooper::factory()->create())
            ->asGoing()
            ->create();
        $event_trooper_two = EventTrooper::factory()
            ->forEventShift($shift_one)
            ->forTrooper(Trooper::factory()->create())
            ->asTentative()
            ->create();
        $event_trooper_three = EventTrooper::factory()
            ->forEventShift($shift_two)
            ->forTrooper(Trooper::factory()->create())
            ->state([EventTrooper::STATUS => EventTrooperStatus::CANCELLED])
            ->create();

        $trooper_one = Trooper::factory()->create();
        $trooper_two = Trooper::factory()->create();

        $messages = $this->fakeMessages([
            GetTroopersForEventCancelled::class => collect([$trooper_one, $trooper_two]),
            SendEventCancelledNotification::class => fn (array $params): mixed => match (true)
            {
                $params['event']->id === $event->id && $params['trooper']->id === $trooper_one->id => null,
                $params['event']->id === $event->id && $params['trooper']->id === $trooper_two->id => null,
            },
        ]);

        $subject = new SendEventCancelledNotificationsJob($event);
        $subject->handle();

        $messages->assertCalled(GetTroopersForEventCancelled::class);
        $messages->assertCalled(
            SendEventCancelledNotification::class,
            fn (array $params): bool => $params['event']->id === $event->id && $params['trooper']->id === $trooper_one->id
        );
        $messages->assertCalled(
            SendEventCancelledNotification::class,
            fn (array $params): bool => $params['event']->id === $event->id && $params['trooper']->id === $trooper_two->id
        );

        $this->assertNotNull($event->fresh()->cancel_notifications_sent_at);
        $this->assertSame(EventStatus::CANCELLED, $shift_one->fresh()->status);
        $this->assertSame(EventStatus::CANCELLED, $shift_two->fresh()->status);

        $this->assertSame(EventTrooperStatus::CANCELLED, $event_trooper_one->fresh()->status);
        $this->assertSame(EventTrooperStatus::CANCELLED, $event_trooper_two->fresh()->status);
        $this->assertSame(EventTrooperStatus::CANCELLED, $event_trooper_three->fresh()->status);
    }

    public function test_handle_rolls_back_marker_and_statuses_when_dispatch_fails(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper(Trooper::factory()->create())
            ->asGoing()
            ->create();

        $trooper_one = Trooper::factory()->create();
        $trooper_two = Trooper::factory()->create();

        $messages = $this->fakeMessages([
            GetTroopersForEventCancelled::class => collect([$trooper_one, $trooper_two]),
            SendEventCancelledNotification::class => fn (array $params): mixed => match (true)
            {
                $params['trooper']->id === $trooper_one->id => null,
                $params['trooper']->id === $trooper_two->id => throw new Exception('dispatch failed'),
            },
        ]);

        $subject = new SendEventCancelledNotificationsJob($event);

        $this->expectException(Exception::class);

        try
        {
            $subject->handle();
        }
        finally
        {
            // Everything rolls back so a retry still finds the GOING roster and
            // re-sends to all of it.
            $this->assertNull($event->fresh()->cancel_notifications_sent_at);
            $this->assertSame(EventStatus::OPEN, $shift->fresh()->status);
            $this->assertSame(EventTrooperStatus::GOING, $event_trooper->fresh()->status);
        }
    }

    public function test_handle_returns_early_when_notifications_were_already_sent(): void
    {
        $event = Event::factory()->withCancelNotificationsSent()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper(Trooper::factory()->create())
            ->asGoing()
            ->create();

        $messages = $this->fakeMessages([]);

        $subject = new SendEventCancelledNotificationsJob($event);
        $subject->handle();

        $messages->assertNotCalled(GetTroopersForEventCancelled::class);
        $messages->assertNotCalled(SendEventCancelledNotification::class);

        $this->assertNotNull($event->fresh()->cancel_notifications_sent_at);
        $this->assertSame(EventStatus::OPEN, $shift->fresh()->status);
        $this->assertSame(EventTrooperStatus::GOING, $event_trooper->fresh()->status);
    }
}
