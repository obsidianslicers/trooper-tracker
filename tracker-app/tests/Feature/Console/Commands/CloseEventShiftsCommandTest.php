<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Bus\MagicBus;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Features\Events\Queries\GetEventShiftsToCloseQuery;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Notifications\Events\EventShiftCompletedNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Test suite for CloseEventShiftsCommand.
 *
 * Verifies that the command correctly identifies and closes event shifts
 * whose end time has passed, updates their status to CLOSED, and sends
 * completion emails to attending troopers.
 */
class CloseEventShiftsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the command closes event shifts returned by the query.
     */
    public function test_command_closes_event_shifts_returned_by_query(): void
    {
        // Arrange: Create test event shifts
        $shift1 = EventShift::factory()->create([EventShift::STATUS => EventStatus::OPEN, EventShift::SHIFT_ENDS_AT => Carbon::now()->subHour()]);
        $shift2 = EventShift::factory()->create([EventShift::STATUS => EventStatus::OPEN, EventShift::SHIFT_ENDS_AT => Carbon::now()->subHour()]);
        $shifts = collect([$shift1, $shift2]);

        // Mock MagicBus to return shifts to close
        $this->mock(MagicBus::class, function (MockInterface $mock) use ($shifts)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetEventShiftsToCloseQuery::class))
                ->andReturn($shifts);
        });

        // Act: Execute the command
        $this->artisan('tracker:close-event-shifts')
            ->assertExitCode(0);

        // Assert: Verify shifts are closed
        $shift1->refresh();
        $shift2->refresh();
        $this->assertEquals(EventStatus::CLOSED, $shift1->status);
        $this->assertEquals(EventStatus::CLOSED, $shift2->status);
    }

    /**
     * Test that the command sends notifications to troopers with GOING status.
     */
    public function test_command_sends_notifications_to_going_troopers(): void
    {
        // Arrange: Create event shift with troopers in different statuses
        $shift = EventShift::factory()->create([EventShift::STATUS => EventStatus::OPEN, EventShift::SHIFT_ENDS_AT => Carbon::now()->subHour()]);

        $trooper_going = Mockery::mock(Trooper::class)->makePartial();
        $trooper_going->shouldReceive('notify')
            ->once()
            ->with(Mockery::type(EventShiftCompletedNotification::class));

        $event_trooper_going = EventTrooper::factory()->make([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);
        $event_trooper_going->setRelation('trooper', $trooper_going);

        $trooper_cancelled = Mockery::mock(Trooper::class)->makePartial();
        $trooper_cancelled->shouldNotReceive('notify');

        $event_trooper_cancelled = EventTrooper::factory()->make([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::CANCELLED,
        ]);
        $event_trooper_cancelled->setRelation('trooper', $trooper_cancelled);

        $shift->setRelation(
            'event_troopers',
            collect([$event_trooper_going, $event_trooper_cancelled])
        );

        // Mock MagicBus
        $this->mock(MagicBus::class, function (MockInterface $mock) use ($shift)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetEventShiftsToCloseQuery::class))
                ->andReturn(collect([$shift]));
        });

        // Act: Execute the command
        $this->artisan('tracker:close-event-shifts')
            ->assertExitCode(0);
    }

    /**
     * Test that the command does not notify non-GOING troopers.
     */
    public function test_command_does_not_notify_non_going_troopers(): void
    {
        // Arrange: Create event shift with troopers not going
        $shift = EventShift::factory()->create([EventShift::STATUS => EventStatus::OPEN, EventShift::SHIFT_ENDS_AT => Carbon::now()->subHour()]);

        $trooper1 = Mockery::mock(Trooper::class)->makePartial();
        $trooper1->shouldNotReceive('notify');
        $event_trooper1 = EventTrooper::factory()->make([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::TENTATIVE,
        ]);
        $event_trooper1->setRelation('trooper', $trooper1);

        $trooper2 = Mockery::mock(Trooper::class)->makePartial();
        $trooper2->shouldNotReceive('notify');
        $event_trooper2 = EventTrooper::factory()->make([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::PENDING,
        ]);
        $event_trooper2->setRelation('trooper', $trooper2);

        $shift->setRelation('event_troopers', collect([$event_trooper1, $event_trooper2]));

        // Mock MagicBus
        $this->mock(MagicBus::class, function (MockInterface $mock) use ($shift)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetEventShiftsToCloseQuery::class))
                ->andReturn(collect([$shift]));
        });

        // Act: Execute the command
        $this->artisan('tracker:close-event-shifts')
            ->assertExitCode(0);
    }

    /**
     * Test that the command handles empty shift collection gracefully.
     */
    public function test_command_handles_no_shifts_to_close(): void
    {
        // Arrange
        $this->mock(MagicBus::class, function (MockInterface $mock)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetEventShiftsToCloseQuery::class))
                ->andReturn(collect([]));
        });

        // Act & Assert: Command should complete successfully
        $this->artisan('tracker:close-event-shifts')
            ->assertExitCode(0);
    }

    /**
     * Test that the command notifies multiple GOING troopers in same shift.
     */
    public function test_command_sends_notifications_to_multiple_going_troopers(): void
    {
        // Arrange: Create shift with multiple GOING troopers
        $shift = EventShift::factory()->create([EventShift::STATUS => EventStatus::OPEN, EventShift::SHIFT_ENDS_AT => Carbon::now()->subHour()]);

        $trooper1 = Mockery::mock(Trooper::class)->makePartial();
        $trooper1->shouldReceive('notify')
            ->once()
            ->with(Mockery::type(EventShiftCompletedNotification::class));

        $trooper2 = Mockery::mock(Trooper::class)->makePartial();
        $trooper2->shouldReceive('notify')
            ->once()
            ->with(Mockery::type(EventShiftCompletedNotification::class));

        $trooper3 = Mockery::mock(Trooper::class)->makePartial();
        $trooper3->shouldReceive('notify')
            ->once()
            ->with(Mockery::type(EventShiftCompletedNotification::class));

        $event_trooper1 = EventTrooper::factory()->make([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);
        $event_trooper1->setRelation('trooper', $trooper1);

        $event_trooper2 = EventTrooper::factory()->make([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);
        $event_trooper2->setRelation('trooper', $trooper2);

        $event_trooper3 = EventTrooper::factory()->make([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);
        $event_trooper3->setRelation('trooper', $trooper3);

        $shift->setRelation(
            'event_troopers',
            collect([$event_trooper1, $event_trooper2, $event_trooper3])
        );

        // Mock MagicBus
        $this->mock(MagicBus::class, function (MockInterface $mock) use ($shift)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetEventShiftsToCloseQuery::class))
                ->andReturn(collect([$shift]));
        });

        // Act: Execute the command
        $this->artisan('tracker:close-event-shifts')
            ->assertExitCode(0);
    }

    /**
     * Test that the command processes multiple shifts correctly.
     */
    public function test_command_processes_multiple_shifts(): void
    {
        // Arrange: Create multiple shifts
        $shift1 = EventShift::factory()->create([EventShift::STATUS => EventStatus::OPEN, EventShift::SHIFT_ENDS_AT => Carbon::now()->subHour()]);
        $shift2 = EventShift::factory()->create([EventShift::STATUS => EventStatus::OPEN, EventShift::SHIFT_ENDS_AT => Carbon::now()->subHour()]);

        $trooper1 = Mockery::mock(Trooper::class)->makePartial();
        $trooper1->shouldReceive('notify')
            ->once()
            ->with(Mockery::type(EventShiftCompletedNotification::class));

        $event_trooper1 = EventTrooper::factory()->make([
            EventTrooper::EVENT_SHIFT_ID => $shift1->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);
        $event_trooper1->setRelation('trooper', $trooper1);
        $shift1->setRelation('event_troopers', collect([$event_trooper1]));

        $trooper2 = Mockery::mock(Trooper::class)->makePartial();
        $trooper2->shouldReceive('notify')
            ->once()
            ->with(Mockery::type(EventShiftCompletedNotification::class));

        $event_trooper2 = EventTrooper::factory()->make([
            EventTrooper::EVENT_SHIFT_ID => $shift2->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);
        $event_trooper2->setRelation('trooper', $trooper2);
        $shift2->setRelation('event_troopers', collect([$event_trooper2]));

        // Mock MagicBus
        $this->mock(MagicBus::class, function (MockInterface $mock) use ($shift1, $shift2)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetEventShiftsToCloseQuery::class))
                ->andReturn(collect([$shift1, $shift2]));
        });

        // Act: Execute the command
        $this->artisan('tracker:close-event-shifts')
            ->assertExitCode(0);

        // Assert: Both shifts closed and both troopers emailed
        $shift1->refresh();
        $shift2->refresh();
        $this->assertEquals(EventStatus::CLOSED, $shift1->status);
        $this->assertEquals(EventStatus::CLOSED, $shift2->status);
    }

    /**
     * Test that the command has the correct signature.
     */
    public function test_command_has_correct_signature(): void
    {
        // Act: Get all registered commands
        $commands = $this->app['Illuminate\Contracts\Console\Kernel']->all();

        // Assert: Verify command exists
        $this->assertArrayHasKey('tracker:close-event-shifts', $commands);
    }
}
