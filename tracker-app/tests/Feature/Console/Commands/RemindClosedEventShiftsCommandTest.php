<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Bus\MagicBus;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Features\Events\Queries\GetEventShiftsToRemindQuery;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Notifications\Events\EventShiftCompletedNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class RemindClosedEventShiftsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_sends_notification_to_going_trooper(): void
    {
        $shift = EventShift::factory()->create([EventShift::STATUS => EventStatus::CLOSED, EventShift::SHIFT_ENDS_AT => Carbon::now()->subHour()]);

        $trooper = Mockery::mock(Trooper::class)->makePartial();
        $trooper->shouldReceive('notify')
            ->once()
            ->with(Mockery::type(EventShiftCompletedNotification::class));

        $event_trooper = EventTrooper::factory()->make([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);
        $event_trooper->setRelation('trooper', $trooper);
        $shift->setRelation('event_troopers', collect([$event_trooper]));

        $this->mock(MagicBus::class, function (MockInterface $mock) use ($shift)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetEventShiftsToRemindQuery::class))
                ->andReturn(collect([$shift]));
        });

        $this->artisan('tracker:remind-closed-event-shifts')->assertExitCode(0);
    }

    public function test_command_sends_notification_to_tentative_trooper(): void
    {
        $shift = EventShift::factory()->create([EventShift::STATUS => EventStatus::CLOSED, EventShift::SHIFT_ENDS_AT => Carbon::now()->subHour()]);

        $trooper = Mockery::mock(Trooper::class)->makePartial();
        $trooper->shouldReceive('notify')
            ->once()
            ->with(Mockery::type(EventShiftCompletedNotification::class));

        $event_trooper = EventTrooper::factory()->make([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::TENTATIVE,
        ]);
        $event_trooper->setRelation('trooper', $trooper);
        $shift->setRelation('event_troopers', collect([$event_trooper]));

        $this->mock(MagicBus::class, function (MockInterface $mock) use ($shift)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetEventShiftsToRemindQuery::class))
                ->andReturn(collect([$shift]));
        });

        $this->artisan('tracker:remind-closed-event-shifts')->assertExitCode(0);
    }

    public function test_command_does_not_notify_cancelled_troopers(): void
    {
        $shift = EventShift::factory()->create([EventShift::STATUS => EventStatus::CLOSED, EventShift::SHIFT_ENDS_AT => Carbon::now()->subHour()]);

        $trooper = Mockery::mock(Trooper::class)->makePartial();
        $trooper->shouldNotReceive('notify');

        $event_trooper = EventTrooper::factory()->make([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::CANCELLED,
        ]);
        $event_trooper->setRelation('trooper', $trooper);
        $shift->setRelation('event_troopers', collect([$event_trooper]));

        $this->mock(MagicBus::class, function (MockInterface $mock) use ($shift)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetEventShiftsToRemindQuery::class))
                ->andReturn(collect([$shift]));
        });

        $this->artisan('tracker:remind-closed-event-shifts')->assertExitCode(0);
    }

    public function test_command_sets_last_notified_at_on_processed_shifts(): void
    {
        $shift = EventShift::factory()->create([
            EventShift::STATUS => EventStatus::CLOSED,
            EventShift::SHIFT_ENDS_AT => Carbon::now()->subHour(),
            EventShift::LAST_NOTIFIED_AT => null,
        ]);
        $shift->setRelation('event_troopers', collect([]));

        $this->mock(MagicBus::class, function (MockInterface $mock) use ($shift)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetEventShiftsToRemindQuery::class))
                ->andReturn(collect([$shift]));
        });

        $this->artisan('tracker:remind-closed-event-shifts')->assertExitCode(0);

        $shift->refresh();
        $this->assertNotNull($shift->last_notified_at);
    }

    public function test_command_handles_no_shifts_gracefully(): void
    {
        $this->mock(MagicBus::class, function (MockInterface $mock)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetEventShiftsToRemindQuery::class))
                ->andReturn(collect([]));
        });

        $this->artisan('tracker:remind-closed-event-shifts')->assertExitCode(0);
    }

    public function test_command_notifies_multiple_troopers_across_multiple_shifts(): void
    {
        $shift1 = EventShift::factory()->create([EventShift::STATUS => EventStatus::CLOSED, EventShift::SHIFT_ENDS_AT => Carbon::now()->subHour()]);
        $shift2 = EventShift::factory()->create([EventShift::STATUS => EventStatus::CLOSED, EventShift::SHIFT_ENDS_AT => Carbon::now()->subHour()]);

        $trooper1 = Mockery::mock(Trooper::class)->makePartial();
        $trooper1->shouldReceive('notify')->once()->with(Mockery::type(EventShiftCompletedNotification::class));
        $event_trooper1 = EventTrooper::factory()->make([EventTrooper::EVENT_SHIFT_ID => $shift1->id, EventTrooper::STATUS => EventTrooperStatus::GOING]);
        $event_trooper1->setRelation('trooper', $trooper1);
        $shift1->setRelation('event_troopers', collect([$event_trooper1]));

        $trooper2 = Mockery::mock(Trooper::class)->makePartial();
        $trooper2->shouldReceive('notify')->once()->with(Mockery::type(EventShiftCompletedNotification::class));
        $event_trooper2 = EventTrooper::factory()->make([EventTrooper::EVENT_SHIFT_ID => $shift2->id, EventTrooper::STATUS => EventTrooperStatus::TENTATIVE]);
        $event_trooper2->setRelation('trooper', $trooper2);
        $shift2->setRelation('event_troopers', collect([$event_trooper2]));

        $this->mock(MagicBus::class, function (MockInterface $mock) use ($shift1, $shift2)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetEventShiftsToRemindQuery::class))
                ->andReturn(collect([$shift1, $shift2]));
        });

        $this->artisan('tracker:remind-closed-event-shifts')->assertExitCode(0);
    }

    public function test_command_has_correct_signature(): void
    {
        $commands = $this->app['Illuminate\Contracts\Console\Kernel']->all();

        $this->assertArrayHasKey('tracker:remind-closed-event-shifts', $commands);
    }
}
