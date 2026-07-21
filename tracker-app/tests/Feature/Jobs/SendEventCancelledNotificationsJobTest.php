<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Bus\MagicBus;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Features\Events\Commands\SendEventCancelledNotificationCommand;
use App\Features\Events\Queries\GetTroopersForEventCancelledQuery;
use App\Jobs\SendEventCancelledNotificationsJob;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SendEventCancelledNotificationsJobTest extends TestCase
{
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

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn(object $query): bool => $query instanceof GetTroopersForEventCancelledQuery)
            ->andReturn(collect([$trooper_one, $trooper_two]));

        $bus->shouldReceive('send')
            ->once()
            ->withArgs(function (object $command) use ($event, $trooper_one): bool
            {
                return $command instanceof SendEventCancelledNotificationCommand
                    && $command->event->id === $event->id
                    && $command->trooper->id === $trooper_one->id;
            });

        $bus->shouldReceive('send')
            ->once()
            ->withArgs(function (object $command) use ($event, $trooper_two): bool
            {
                return $command instanceof SendEventCancelledNotificationCommand
                    && $command->event->id === $event->id
                    && $command->trooper->id === $trooper_two->id;
            });

        $subject = new SendEventCancelledNotificationsJob($event);
        $subject->handle($bus);

        $this->assertNotNull($event->fresh()->cancel_notifications_sent_at);
        $this->assertSame(EventStatus::CANCELLED, $shift_one->fresh()->status);
        $this->assertSame(EventStatus::CANCELLED, $shift_two->fresh()->status);

        $this->assertSame(EventTrooperStatus::CANCELLED, $event_trooper_one->fresh()->status);
        $this->assertSame(EventTrooperStatus::CANCELLED, $event_trooper_two->fresh()->status);
        $this->assertSame(EventTrooperStatus::CANCELLED, $event_trooper_three->fresh()->status);
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

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldNotReceive('send');

        $subject = new SendEventCancelledNotificationsJob($event);
        $subject->handle($bus);

        $this->assertNotNull($event->fresh()->cancel_notifications_sent_at);
        $this->assertSame(EventStatus::OPEN, $shift->fresh()->status);
        $this->assertSame(EventTrooperStatus::GOING, $event_trooper->fresh()->status);
    }
}
