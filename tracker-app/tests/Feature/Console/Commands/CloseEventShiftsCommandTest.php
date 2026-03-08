<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Bus\MagicBus;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Features\Events\Queries\GetEventShiftsToCloseQuery;
use App\Mail\Events\EventShiftComplete;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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
        $shift1 = EventShift::factory()->create([EventShift::STATUS => EventStatus::OPEN]);
        $shift2 = EventShift::factory()->create([EventShift::STATUS => EventStatus::OPEN]);
        $shifts = collect([$shift1, $shift2]);

        Mail::fake();

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
     * Test that the command sends emails to troopers with GOING status.
     */
    public function test_command_sends_emails_to_going_troopers(): void
    {
        // Arrange: Create event shift with troopers in different statuses
        $shift = EventShift::factory()->create([EventShift::STATUS => EventStatus::OPEN]);

        $trooper_going = Trooper::factory()->create();
        $event_trooper_going = EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper_going->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $trooper_cancelled = Trooper::factory()->create();
        $event_trooper_cancelled = EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper_cancelled->id,
            EventTrooper::STATUS => EventTrooperStatus::CANCELLED,
        ]);

        Mail::fake();

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

        // Assert: Only GOING trooper receives email
        Mail::assertQueued(EventShiftComplete::class, function ($mail) use ($trooper_going)
        {
            return $mail->hasTo($trooper_going->email);
        });

        Mail::assertQueued(EventShiftComplete::class, 1);
    }

    /**
     * Test that the command does not send emails to non-GOING troopers.
     */
    public function test_command_does_not_send_emails_to_non_going_troopers(): void
    {
        // Arrange: Create event shift with troopers not going
        $shift = EventShift::factory()->create([EventShift::STATUS => EventStatus::OPEN]);

        $trooper1 = Trooper::factory()->create();
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper1->id,
            EventTrooper::STATUS => EventTrooperStatus::TENTATIVE,
        ]);

        $trooper2 = Trooper::factory()->create();
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper2->id,
            EventTrooper::STATUS => EventTrooperStatus::PENDING,
        ]);

        Mail::fake();

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

        // Assert: No emails queued
        Mail::assertNothingQueued();
    }

    /**
     * Test that the command handles empty shift collection gracefully.
     */
    public function test_command_handles_no_shifts_to_close(): void
    {
        // Arrange
        Mail::fake();

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

        Mail::assertNothingQueued();
    }

    /**
     * Test that the command sends emails to multiple GOING troopers in same shift.
     */
    public function test_command_sends_emails_to_multiple_going_troopers(): void
    {
        // Arrange: Create shift with multiple GOING troopers
        $shift = EventShift::factory()->create([EventShift::STATUS => EventStatus::OPEN]);

        $trooper1 = Trooper::factory()->create();
        $trooper2 = Trooper::factory()->create();
        $trooper3 = Trooper::factory()->create();

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper1->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper2->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper3->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        Mail::fake();

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

        // Assert: All three troopers receive emails
        Mail::assertQueued(EventShiftComplete::class, 3);
        Mail::assertQueued(EventShiftComplete::class, function ($mail) use ($trooper1)
        {
            return $mail->hasTo($trooper1->email);
        });
        Mail::assertQueued(EventShiftComplete::class, function ($mail) use ($trooper2)
        {
            return $mail->hasTo($trooper2->email);
        });
        Mail::assertQueued(EventShiftComplete::class, function ($mail) use ($trooper3)
        {
            return $mail->hasTo($trooper3->email);
        });
    }

    /**
     * Test that the command processes multiple shifts correctly.
     */
    public function test_command_processes_multiple_shifts(): void
    {
        // Arrange: Create multiple shifts
        $shift1 = EventShift::factory()->create([EventShift::STATUS => EventStatus::OPEN]);
        $shift2 = EventShift::factory()->create([EventShift::STATUS => EventStatus::OPEN]);

        $trooper1 = Trooper::factory()->create();
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift1->id,
            EventTrooper::TROOPER_ID => $trooper1->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $trooper2 = Trooper::factory()->create();
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift2->id,
            EventTrooper::TROOPER_ID => $trooper2->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        Mail::fake();

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

        Mail::assertQueued(EventShiftComplete::class, 2);
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
