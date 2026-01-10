<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Mail\Events\EventShiftComplete;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Services\Events\GetEventShiftsToCloseQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CloseEventShiftsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_closes_event_shifts_that_have_ended(): void
    {
        // Arrange
        $ended_shift = EventShift::factory()->create([
            'status' => EventStatus::OPEN,
            'shift_ends_at' => Carbon::yesterday(),
        ]);

        // Act
        $this->artisan('tracker:close-event-shifts')->assertExitCode(0);

        // Assert
        $this->assertEquals(EventStatus::CLOSED, $ended_shift->fresh()->status);
    }

    public function test_it_closes_multiple_event_shifts_that_have_ended(): void
    {
        // Arrange
        $ended_shift1 = EventShift::factory()->create([
            'status' => EventStatus::OPEN,
            'shift_ends_at' => Carbon::yesterday(),
        ]);

        $ended_shift2 = EventShift::factory()->create([
            'status' => EventStatus::DRAFT,
            'shift_ends_at' => Carbon::parse('-2 days'),
        ]);

        $ended_shift3 = EventShift::factory()->create([
            'status' => EventStatus::SIGN_UP_LOCKED,
            'shift_ends_at' => Carbon::parse('-1 week'),
        ]);

        // Act
        $this->artisan('tracker:close-event-shifts')->assertExitCode(0);

        // Assert
        $this->assertEquals(EventStatus::CLOSED, $ended_shift1->fresh()->status);
        $this->assertEquals(EventStatus::CLOSED, $ended_shift2->fresh()->status);
        $this->assertEquals(EventStatus::CLOSED, $ended_shift3->fresh()->status);
    }

    public function test_it_does_not_close_event_shifts_that_have_not_ended(): void
    {
        // Arrange
        $future_shift = EventShift::factory()->create([
            'status' => EventStatus::OPEN,
            'shift_ends_at' => Carbon::tomorrow(),
        ]);

        // Act
        $this->artisan('tracker:close-event-shifts')->assertExitCode(0);

        // Assert
        $this->assertEquals(EventStatus::OPEN, $future_shift->fresh()->status);
    }

    public function test_it_does_not_affect_already_closed_event_shifts(): void
    {
        // Arrange
        $closed_shift = EventShift::factory()->create([
            'status' => EventStatus::CLOSED,
            'shift_ends_at' => Carbon::yesterday(),
        ]);

        // Act
        $this->artisan('tracker:close-event-shifts')->assertExitCode(0);

        // Assert
        $this->assertEquals(EventStatus::CLOSED, $closed_shift->fresh()->status);
    }

    public function test_it_does_not_affect_cancelled_event_shifts(): void
    {
        // Arrange
        $cancelled_shift = EventShift::factory()->create([
            'status' => EventStatus::CANCELLED,
            'shift_ends_at' => Carbon::yesterday(),
        ]);

        // Act
        $this->artisan('tracker:close-event-shifts')->assertExitCode(0);

        // Assert
        $this->assertEquals(EventStatus::CANCELLED, $cancelled_shift->fresh()->status);
    }

    public function test_it_sends_completion_emails_to_troopers_with_going_status(): void
    {
        Mail::fake();

        // Arrange
        $costume = OrganizationCostume::factory()->create();
        $trooper = Trooper::factory()->create();

        $ended_shift = EventShift::factory()->create([
            'status' => EventStatus::OPEN,
            'shift_ends_at' => Carbon::yesterday(),
        ]);

        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $ended_shift->id,
            'trooper_id' => $trooper->id,
            'costume_id' => $costume->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        // Act
        $this->artisan('tracker:close-event-shifts')->assertExitCode(0);

        // Assert
        Mail::assertQueued(EventShiftComplete::class, function ($mail) use ($trooper)
        {
            return $mail->hasTo($trooper->email);
        });
    }

    public function test_it_does_not_send_emails_to_troopers_with_cancelled_status(): void
    {
        Mail::fake();

        // Arrange
        $costume = OrganizationCostume::factory()->create();
        $trooper = Trooper::factory()->create();

        $ended_shift = EventShift::factory()->create([
            'status' => EventStatus::OPEN,
            'shift_ends_at' => Carbon::yesterday(),
        ]);

        EventTrooper::factory()->create([
            'event_shift_id' => $ended_shift->id,
            'trooper_id' => $trooper->id,
            'costume_id' => $costume->id,
            'status' => EventTrooperStatus::CANCELLED,
        ]);

        // Act
        $this->artisan('tracker:close-event-shifts')->assertExitCode(0);

        // Assert
        Mail::assertNothingQueued();
    }

    public function test_it_does_not_send_emails_to_troopers_with_tentative_status(): void
    {
        Mail::fake();

        // Arrange
        $costume = OrganizationCostume::factory()->create();
        $trooper = Trooper::factory()->create();

        $ended_shift = EventShift::factory()->create([
            'status' => EventStatus::OPEN,
            'shift_ends_at' => Carbon::yesterday(),
        ]);

        EventTrooper::factory()->create([
            'event_shift_id' => $ended_shift->id,
            'trooper_id' => $trooper->id,
            'costume_id' => $costume->id,
            'status' => EventTrooperStatus::TENTATIVE,
        ]);

        // Act
        $this->artisan('tracker:close-event-shifts')->assertExitCode(0);

        // Assert
        Mail::assertNothingQueued();
    }

    public function test_it_sends_multiple_emails_when_multiple_troopers_attended(): void
    {
        Mail::fake();

        // Arrange
        $costume = OrganizationCostume::factory()->create();
        $trooper1 = Trooper::factory()->create();
        $trooper2 = Trooper::factory()->create();
        $trooper3 = Trooper::factory()->create();

        $ended_shift = EventShift::factory()->create([
            'status' => EventStatus::OPEN,
            'shift_ends_at' => Carbon::yesterday(),
        ]);

        EventTrooper::factory()->create([
            'event_shift_id' => $ended_shift->id,
            'trooper_id' => $trooper1->id,
            'costume_id' => $costume->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        EventTrooper::factory()->create([
            'event_shift_id' => $ended_shift->id,
            'trooper_id' => $trooper2->id,
            'costume_id' => $costume->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        EventTrooper::factory()->create([
            'event_shift_id' => $ended_shift->id,
            'trooper_id' => $trooper3->id,
            'costume_id' => $costume->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        // Act
        $this->artisan('tracker:close-event-shifts')->assertExitCode(0);

        // Assert
        Mail::assertQueued(EventShiftComplete::class, 3);
        Mail::assertQueued(EventShiftComplete::class, fn($mail) => $mail->hasTo($trooper1->email));
        Mail::assertQueued(EventShiftComplete::class, fn($mail) => $mail->hasTo($trooper2->email));
        Mail::assertQueued(EventShiftComplete::class, fn($mail) => $mail->hasTo($trooper3->email));
    }

    public function test_it_handles_no_event_shifts_to_close_gracefully(): void
    {
        Mail::fake();

        // Arrange - only future shifts
        EventShift::factory()->count(3)->create([
            'status' => EventStatus::OPEN,
            'shift_ends_at' => Carbon::tomorrow(),
        ]);

        // Act & Assert - should complete without errors
        $this->artisan('tracker:close-event-shifts')->assertExitCode(0);

        Mail::assertNothingQueued();
    }

    public function test_it_uses_get_event_shifts_to_close_query_service(): void
    {
        // Arrange
        $ended_shift = EventShift::factory()->create([
            'status' => EventStatus::OPEN,
            'shift_ends_at' => Carbon::yesterday(),
        ]);

        $service = app(GetEventShiftsToCloseQuery::class);
        $shifts_before = $service();

        // Act
        $this->artisan('tracker:close-event-shifts')->assertExitCode(0);

        // Assert - verify service would no longer return this shift
        $shifts_after = $service();
        $this->assertCount(1, $shifts_before);
        $this->assertCount(0, $shifts_after);
    }
}
