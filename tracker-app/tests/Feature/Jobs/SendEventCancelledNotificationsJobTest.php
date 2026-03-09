<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Bus\MagicBus;
use App\Features\Events\Commands\SendEventCancelledNotificationCommand;
use App\Features\Events\Queries\GetTroopersForEventCancelledQuery;
use App\Jobs\SendEventCancelledNotificationsJob;
use App\Models\Event;
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
    }

    public function test_handle_returns_early_when_notifications_were_already_sent(): void
    {
        $event = Event::factory()->withCancelNotificationsSent()->create();

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldNotReceive('send');

        $subject = new SendEventCancelledNotificationsJob($event);
        $subject->handle($bus);

        $this->assertNotNull($event->fresh()->cancel_notifications_sent_at);
    }
}
