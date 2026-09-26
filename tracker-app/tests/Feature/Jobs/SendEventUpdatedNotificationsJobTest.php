<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Bus\MagicBus;
use App\Features\Events\Commands\SendEventUpdatedNotificationCommand;
use App\Features\Events\Queries\GetTroopersForEventUpdatedQuery;
use App\Jobs\SendEventUpdatedNotificationsJob;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SendEventUpdatedNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sends_command_for_each_trooper_returned_by_query(): void
    {
        $event = Event::factory()->create();
        $changed_fields = [Event::NAME, Event::VENUE];
        $trooper_one = Trooper::factory()->create();
        $trooper_two = Trooper::factory()->create();

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn (object $query): bool => $query instanceof GetTroopersForEventUpdatedQuery
                && $query->event->id === $event->id)
            ->andReturn(collect([$trooper_one, $trooper_two]));

        $bus->shouldReceive('send')
            ->once()
            ->withArgs(function (object $command) use ($event, $trooper_one, $changed_fields): bool {
                return $command instanceof SendEventUpdatedNotificationCommand
                    && $command->event->id === $event->id
                    && $command->trooper->id === $trooper_one->id
                    && $command->changed_fields === $changed_fields;
            });

        $bus->shouldReceive('send')
            ->once()
            ->withArgs(function (object $command) use ($event, $trooper_two, $changed_fields): bool {
                return $command instanceof SendEventUpdatedNotificationCommand
                    && $command->event->id === $event->id
                    && $command->trooper->id === $trooper_two->id
                    && $command->changed_fields === $changed_fields;
            });

        $subject = new SendEventUpdatedNotificationsJob($event, $changed_fields);
        $subject->handle($bus);
    }

    public function test_handle_sends_no_commands_when_query_returns_no_troopers(): void
    {
        $event = Event::factory()->create();

        $bus = Mockery::mock(MagicBus::class);
        $bus->shouldReceive('send')
            ->once()
            ->withArgs(fn (object $query): bool => $query instanceof GetTroopersForEventUpdatedQuery)
            ->andReturn(collect());

        $subject = new SendEventUpdatedNotificationsJob($event, [Event::NAME]);
        $subject->handle($bus);

        $this->addToAssertionCount(1);
    }
}
