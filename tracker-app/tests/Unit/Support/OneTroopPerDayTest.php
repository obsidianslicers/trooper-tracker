<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Support\OneTroopPerDay;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OneTroopPerDayTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $trooper;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tracker.count_as_one_troop_per_day' => true]);
        $this->trooper = Trooper::factory()->create();
    }

    public function test_two_shifts_of_an_event_on_one_day_count_as_one_troop(): void
    {
        $event = Event::factory()->asClosed()->create();
        $morning = $this->attend($event, '2026-12-05 09:00');
        $this->attend($event, '2026-12-05 13:00');

        $this->assertSame([$morning->id], $this->countedIds());
    }

    public function test_every_shift_counts_while_the_rule_is_off(): void
    {
        config(['tracker.count_as_one_troop_per_day' => false]);
        $event = Event::factory()->asClosed()->create();
        $this->attend($event, '2026-12-05 09:00');
        $this->attend($event, '2026-12-05 13:00');

        $this->assertCount(2, $this->countedIds());
        $this->assertFalse(OneTroopPerDay::isOn());
    }

    public function test_shifts_on_different_days_or_at_different_events_each_count(): void
    {
        $convention = Event::factory()->asClosed()->create();
        $hospital = Event::factory()->asClosed()->create();
        $this->attend($convention, '2026-12-05 09:00');
        $this->attend($convention, '2026-12-06 09:00');
        $this->attend($hospital, '2026-12-05 18:00');
        //  a late shift that starts after midnight belongs to the next day
        $this->attend($convention, '2026-12-05 23:00');
        $this->attend($convention, '2026-12-07 00:30');

        $this->assertCount(4, $this->countedIds());
    }

    public function test_a_missed_first_shift_leaves_the_later_one_counting(): void
    {
        $event = Event::factory()->asClosed()->create();
        $this->attend($event, '2026-12-05 09:00', EventTrooperStatus::UNABLE_TO_ATTEND);
        $afternoon = $this->attend($event, '2026-12-05 13:00');

        $this->assertSame([$afternoon->id], $this->countedIds());
    }

    public function test_other_troopers_on_the_same_shifts_are_counted_on_their_own(): void
    {
        $event = Event::factory()->asClosed()->create();
        $this->attend($event, '2026-12-05 09:00');
        $friend_shift = $this->attend($event, '2026-12-05 13:00');
        $friend = Trooper::factory()->create();
        EventTrooper::factory()->forTrooper($friend)->forEventShift($friend_shift->event_shift)->asAttended()->create();

        $this->assertSame(1, EventTrooper::where(EventTrooper::TROOPER_ID, $friend->id)->counted()->count());
        $this->assertCount(1, $this->countedIds());
    }

    public function test_loaded_sign_ups_get_the_same_answer_as_the_query(): void
    {
        $event = Event::factory()->asClosed()->create();
        $this->attend($event, '2026-12-05 09:00');
        $afternoon = $this->attend($event, '2026-12-05 13:00');
        $evening = $this->attend($event, '2026-12-05 18:00');
        $this->attend($event, '2026-12-06 09:00');

        $loaded = EventTrooper::with('event_shift')->where(EventTrooper::TROOPER_ID, $this->trooper->id)->get();

        $this->assertSame([$afternoon->id => true, $evening->id => true], OneTroopPerDay::repeatIds($loaded));
        $this->assertCount(2, $this->countedIds());
    }

    public function test_an_event_says_so_only_when_it_has_two_shifts_on_a_day(): void
    {
        $one_a_day = Event::factory()->create();
        EventShift::factory()->forEvent($one_a_day)->withShiftStartsAt(Carbon::parse('2026-12-05 09:00'))->create();
        EventShift::factory()->forEvent($one_a_day)->withShiftStartsAt(Carbon::parse('2026-12-06 09:00'))->create();
        $two_a_day = Event::factory()->create();
        EventShift::factory()->forEvent($two_a_day)->withShiftStartsAt(Carbon::parse('2026-12-05 09:00'))->create();
        EventShift::factory()->forEvent($two_a_day)->withShiftStartsAt(Carbon::parse('2026-12-05 13:00'))->create();

        $this->assertNull($one_a_day->troopCreditNotice());
        $this->assertSame(OneTroopPerDay::NOTICE, $two_a_day->troopCreditNotice());

        config(['tracker.count_as_one_troop_per_day' => false]);
        $this->assertNull($two_a_day->fresh()->troopCreditNotice());
    }

    /**
     * @return list<int>
     */
    private function countedIds(): array
    {
        return EventTrooper::where(EventTrooper::TROOPER_ID, $this->trooper->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
            ->counted()
            ->orderBy(EventTrooper::ID)
            ->pluck(EventTrooper::ID)
            ->all();
    }

    private function attend(Event $event, string $starts_at, EventTrooperStatus $status = EventTrooperStatus::ATTENDED): EventTrooper
    {
        $start = Carbon::parse($starts_at);
        $shift = EventShift::factory()
            ->forEvent($event)
            ->withShiftStartsAt($start)
            ->withShiftEndsAt($start->copy()->addHours(3))
            ->create();

        return EventTrooper::factory()
            ->forTrooper($this->trooper)
            ->forEventShift($shift)
            ->create([EventTrooper::STATUS => $status]);
    }
}
