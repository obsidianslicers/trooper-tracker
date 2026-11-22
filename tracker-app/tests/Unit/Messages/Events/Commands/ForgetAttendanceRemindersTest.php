<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Messages\Events\Commands\ForgetAttendanceReminders;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Models\TrooperNotification;
use App\Notifications\Events\EventShiftCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTroopsToConfirm;
use Tests\TestCase;

class ForgetAttendanceRemindersTest extends TestCase
{
    use BuildsTroopsToConfirm;
    use RefreshDatabase;

    private function reminder(Trooper $trooper, array $data): TrooperNotification
    {
        return TrooperNotification::factory()->forTrooper($trooper)->create([
            TrooperNotification::TYPE => EventShiftCompletedNotification::class,
            TrooperNotification::DATA => ['title' => 'Event Shift Completed'] + $data,
        ]);
    }

    public function test_deletes_the_troops_reminders_and_nothing_else(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $troop = $this->troopToConfirm($trooper);
        $other_troop = $this->troopToConfirm($trooper);

        $this->reminder($trooper, [EventShiftCompletedNotification::EVENT_TROOPER_ID => $troop->id]);
        //  written before reminders carried the sign-up's id
        $this->reminder($trooper, ['url' => "/events/details/{$troop->event_shift->event_id}#shift-{$troop->event_shift_id}"]);
        $keep = $this->reminder($trooper, [EventShiftCompletedNotification::EVENT_TROOPER_ID => $other_troop->id]);
        $unrelated = TrooperNotification::factory()->forTrooper($trooper)->create([
            TrooperNotification::DATA => ['title' => 'Event open', EventShiftCompletedNotification::EVENT_TROOPER_ID => $troop->id],
        ]);

        $this->assertSame(2, ForgetAttendanceReminders::call(event_trooper: $troop));

        $this->assertEqualsCanonicalizing([$keep->id, $unrelated->id], TrooperNotification::pluck('id')->all());
    }

    public function test_an_answer_from_anywhere_deletes_the_reminder(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $troop = $this->troopToConfirm($trooper);
        $this->reminder($trooper, [EventShiftCompletedNotification::EVENT_TROOPER_ID => $troop->id]);

        //  e.g. a moderator on the roster
        $troop->update([EventTrooper::STATUS => EventTrooperStatus::ATTENDED]);

        $this->assertSame(0, TrooperNotification::count());
    }

    public function test_a_change_that_still_leaves_it_unanswered_keeps_the_reminder(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $troop = $this->troopToConfirm($trooper, EventTrooperStatus::TENTATIVE);
        $this->reminder($trooper, [EventShiftCompletedNotification::EVENT_TROOPER_ID => $troop->id]);

        $troop->update([EventTrooper::STATUS => EventTrooperStatus::GOING]);

        $this->assertSame(1, TrooperNotification::count());
    }

    public function test_a_new_reminder_replaces_the_last_one(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $troop = $this->troopToConfirm($trooper);
        $troop->event_shift->forceFill([EventShift::LAST_NOTIFIED_AT => now()->subDays(4)])->saveQuietly();
        $this->reminder($trooper, [EventShiftCompletedNotification::EVENT_TROOPER_ID => $troop->id]);

        $this->artisan('tracker:remind-closed-event-shifts')->assertExitCode(0);

        $reminders = TrooperNotification::where(TrooperNotification::TYPE, EventShiftCompletedNotification::class)->get();
        $this->assertCount(1, $reminders);
        $this->assertSame($troop->id, $reminders->first()->data[EventShiftCompletedNotification::EVENT_TROOPER_ID]);
        $this->assertSame(route('events.troops-to-confirm', absolute: false), $reminders->first()->data['url']);
    }
}
