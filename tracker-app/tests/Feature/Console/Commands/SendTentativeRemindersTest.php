<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Bus\MagicBus;
use App\Features\Events\Commands\SendTentativeReminderCommand;
use App\Features\Events\Queries\GetTentativeEventTroopersQuery;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class SendTentativeRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_dispatches_query_and_sends_command_for_each_event_trooper(): void
    {
        $event = Event::factory()->withEventStart(now()->addDays(3))->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $trooper1 = Trooper::factory()->asMember()->create();
        $trooper2 = Trooper::factory()->asMember()->create();
        $et1 = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper1)->asTentative()->create();
        $et2 = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper2)->asTentative()->create();

        $event_troopers = collect([$et1, $et2]);

        $this->mock(MagicBus::class, function (MockInterface $mock) use ($event_troopers, $et1, $et2)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetTentativeEventTroopersQuery::class))
                ->andReturn($event_troopers);

            $mock->shouldReceive('send')
                ->once()
                ->withArgs(fn (SendTentativeReminderCommand $cmd) => $cmd->event_trooper->id === $et1->id)
                ->andReturn(null);

            $mock->shouldReceive('send')
                ->once()
                ->withArgs(fn (SendTentativeReminderCommand $cmd) => $cmd->event_trooper->id === $et2->id)
                ->andReturn(null);
        });

        $this->artisan('tracker:send-tentative-reminders')->assertExitCode(0);
    }

    public function test_command_handles_no_tentative_troopers(): void
    {
        $this->mock(MagicBus::class, function (MockInterface $mock)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetTentativeEventTroopersQuery::class))
                ->andReturn(collect([]));
        });

        $this->artisan('tracker:send-tentative-reminders')->assertExitCode(0);
    }
}
