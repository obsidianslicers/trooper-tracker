<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\EventShift;
use App\Models\EventTrooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite for ReopenFutureShiftsCommand.
 *
 * Verifies that the command correctly identifies future-but-CLOSED shifts,
 * resets their status to OPEN, and reverts any trooper attendance confirmations
 * back to GOING. Also tests the --dry-run flag and interactive confirmation.
 */
class ReopenFutureShiftsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a CLOSED shift whose end time is in the future, bypassing the
     * EventShiftObserver (which normally prevents this invalid state).
     *
     * @param array<string, mixed> $attributes
     */
    private function createFutureClosedShift(array $attributes = []): EventShift
    {
        $shift = EventShift::factory()->make([
            EventShift::STATUS => EventStatus::CLOSED,
            EventShift::SHIFT_STARTS_AT => Carbon::now()->addDays(7)->setTime(10, 0),
            EventShift::SHIFT_ENDS_AT => Carbon::now()->addDays(7)->setTime(14, 0),
            ...$attributes,
        ]);

        $shift->saveQuietly();

        return $shift;
    }

    /**
     * Create a legitimately CLOSED past shift via the normal factory path.
     * The observer allows this because shift_ends_at is in the past.
     */
    private function createPastClosedShift(): EventShift
    {
        return EventShift::factory()
            ->asClosed()
            ->withShiftStartsAt(Carbon::now()->subDay()->setTime(10, 0))
            ->withShiftEndsAt(Carbon::now()->subDay()->setTime(14, 0))
            ->create();
    }

    // ── No-op cases ───────────────────────────────────────────────────────

    public function test_outputs_nothing_to_do_when_no_future_closed_shifts_exist(): void
    {
        $this->artisan('tracker:reopen-future-shifts')
            ->expectsOutput('No future shifts found with CLOSED status. Nothing to do.')
            ->assertExitCode(0);
    }

    public function test_past_closed_shifts_are_ignored_and_nothing_to_do_message_shown(): void
    {
        $past_shift = $this->createPastClosedShift();

        $this->artisan('tracker:reopen-future-shifts')
            ->expectsOutput('No future shifts found with CLOSED status. Nothing to do.')
            ->assertExitCode(0);

        $this->assertEquals(EventStatus::CLOSED, $past_shift->fresh()->status);
    }

    public function test_open_future_shifts_are_not_touched(): void
    {
        EventShift::factory()->create([
            EventShift::STATUS => EventStatus::OPEN,
            EventShift::SHIFT_ENDS_AT => Carbon::now()->addDays(7),
        ]);

        $this->artisan('tracker:reopen-future-shifts')
            ->expectsOutput('No future shifts found with CLOSED status. Nothing to do.')
            ->assertExitCode(0);
    }

    // ── Dry-run ───────────────────────────────────────────────────────────

    public function test_dry_run_previews_shift_without_making_any_changes(): void
    {
        $shift = $this->createFutureClosedShift();

        $this->artisan('tracker:reopen-future-shifts', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertEquals(EventStatus::CLOSED, $shift->fresh()->status);
    }

    public function test_dry_run_does_not_reset_trooper_attendance(): void
    {
        $shift = $this->createFutureClosedShift();
        $event_trooper = EventTrooper::factory()->forEventShift($shift)->create([
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
        ]);

        $this->artisan('tracker:reopen-future-shifts', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertEquals(EventTrooperStatus::ATTENDED, $event_trooper->fresh()->status);
    }

    // ── Confirmation denied ───────────────────────────────────────────────

    public function test_aborts_without_changes_when_user_denies_confirmation(): void
    {
        $shift = $this->createFutureClosedShift();

        $this->artisan('tracker:reopen-future-shifts')
            ->expectsConfirmation('Reopen 1 shift(s) and reset trooper attendance?', 'no')
            ->expectsOutput('Aborted.')
            ->assertExitCode(0);

        $this->assertEquals(EventStatus::CLOSED, $shift->fresh()->status);
    }

    public function test_aborted_command_does_not_reset_trooper_attendance(): void
    {
        $shift = $this->createFutureClosedShift();
        $event_trooper = EventTrooper::factory()->forEventShift($shift)->create([
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
        ]);

        $this->artisan('tracker:reopen-future-shifts')
            ->expectsConfirmation('Reopen 1 shift(s) and reset trooper attendance?', 'no')
            ->assertExitCode(0);

        $this->assertEquals(EventTrooperStatus::ATTENDED, $event_trooper->fresh()->status);
    }

    // ── Confirmed: shift status ───────────────────────────────────────────

    public function test_reopens_single_future_closed_shift_to_open(): void
    {
        $shift = $this->createFutureClosedShift();

        $this->artisan('tracker:reopen-future-shifts')
            ->expectsConfirmation('Reopen 1 shift(s) and reset trooper attendance?', 'yes')
            ->assertExitCode(0);

        $this->assertEquals(EventStatus::OPEN, $shift->fresh()->status);
    }

    public function test_reopens_multiple_future_closed_shifts(): void
    {
        $shift1 = $this->createFutureClosedShift([
            EventShift::SHIFT_ENDS_AT => Carbon::now()->addDays(3)->setTime(14, 0),
        ]);
        $shift2 = $this->createFutureClosedShift([
            EventShift::SHIFT_ENDS_AT => Carbon::now()->addDays(14)->setTime(14, 0),
        ]);

        $this->artisan('tracker:reopen-future-shifts')
            ->expectsConfirmation('Reopen 2 shift(s) and reset trooper attendance?', 'yes')
            ->assertExitCode(0);

        $this->assertEquals(EventStatus::OPEN, $shift1->fresh()->status);
        $this->assertEquals(EventStatus::OPEN, $shift2->fresh()->status);
    }

    public function test_past_closed_shifts_are_unaffected_when_future_ones_are_reopened(): void
    {
        $future_shift = $this->createFutureClosedShift();
        $past_shift = $this->createPastClosedShift();

        $this->artisan('tracker:reopen-future-shifts')
            ->expectsConfirmation('Reopen 1 shift(s) and reset trooper attendance?', 'yes')
            ->assertExitCode(0);

        $this->assertEquals(EventStatus::OPEN, $future_shift->fresh()->status);
        $this->assertEquals(EventStatus::CLOSED, $past_shift->fresh()->status);
    }

    // ── Confirmed: trooper attendance reset ───────────────────────────────

    public function test_resets_attended_trooper_to_going(): void
    {
        $shift = $this->createFutureClosedShift();
        $event_trooper = EventTrooper::factory()->forEventShift($shift)->create([
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
        ]);

        $this->artisan('tracker:reopen-future-shifts')
            ->expectsConfirmation('Reopen 1 shift(s) and reset trooper attendance?', 'yes')
            ->assertExitCode(0);

        $this->assertEquals(EventTrooperStatus::GOING, $event_trooper->fresh()->status);
    }

    public function test_resets_unable_to_attend_trooper_to_going(): void
    {
        $shift = $this->createFutureClosedShift();
        $event_trooper = EventTrooper::factory()->forEventShift($shift)->create([
            EventTrooper::STATUS => EventTrooperStatus::UNABLE_TO_ATTEND,
        ]);

        $this->artisan('tracker:reopen-future-shifts')
            ->expectsConfirmation('Reopen 1 shift(s) and reset trooper attendance?', 'yes')
            ->assertExitCode(0);

        $this->assertEquals(EventTrooperStatus::GOING, $event_trooper->fresh()->status);
    }

    public function test_going_trooper_status_is_unchanged(): void
    {
        $shift = $this->createFutureClosedShift();
        $event_trooper = EventTrooper::factory()->forEventShift($shift)->asGoing()->create();

        $this->artisan('tracker:reopen-future-shifts')
            ->expectsConfirmation('Reopen 1 shift(s) and reset trooper attendance?', 'yes')
            ->assertExitCode(0);

        $this->assertEquals(EventTrooperStatus::GOING, $event_trooper->fresh()->status);
    }

    public function test_resets_all_confirmed_troopers_across_multiple_shifts(): void
    {
        $shift1 = $this->createFutureClosedShift([
            EventShift::SHIFT_ENDS_AT => Carbon::now()->addDays(3)->setTime(14, 0),
        ]);
        $shift2 = $this->createFutureClosedShift([
            EventShift::SHIFT_ENDS_AT => Carbon::now()->addDays(14)->setTime(14, 0),
        ]);

        $attended1 = EventTrooper::factory()->forEventShift($shift1)->create([
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
        ]);
        $unable2 = EventTrooper::factory()->forEventShift($shift2)->create([
            EventTrooper::STATUS => EventTrooperStatus::UNABLE_TO_ATTEND,
        ]);

        $this->artisan('tracker:reopen-future-shifts')
            ->expectsConfirmation('Reopen 2 shift(s) and reset trooper attendance?', 'yes')
            ->assertExitCode(0);

        $this->assertEquals(EventTrooperStatus::GOING, $attended1->fresh()->status);
        $this->assertEquals(EventTrooperStatus::GOING, $unable2->fresh()->status);
    }

    public function test_only_resets_confirmed_troopers_on_future_shifts_not_past_ones(): void
    {
        $future_shift = $this->createFutureClosedShift();
        $past_shift = $this->createPastClosedShift();

        $future_trooper = EventTrooper::factory()->forEventShift($future_shift)->create([
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
        ]);
        $past_trooper = EventTrooper::factory()->forEventShift($past_shift)->create([
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
        ]);

        $this->artisan('tracker:reopen-future-shifts')
            ->expectsConfirmation('Reopen 1 shift(s) and reset trooper attendance?', 'yes')
            ->assertExitCode(0);

        $this->assertEquals(EventTrooperStatus::GOING, $future_trooper->fresh()->status);
        $this->assertEquals(EventTrooperStatus::ATTENDED, $past_trooper->fresh()->status);
    }

    // ── Command registration ──────────────────────────────────────────────

    public function test_command_is_registered(): void
    {
        $commands = $this->app['Illuminate\Contracts\Console\Kernel']->all();

        $this->assertArrayHasKey('tracker:reopen-future-shifts', $commands);
    }
}
