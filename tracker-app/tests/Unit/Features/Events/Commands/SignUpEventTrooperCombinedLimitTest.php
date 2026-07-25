<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Features\Events\Commands\SignUpEventTrooperCommand;
use App\Features\Events\Commands\SignUpEventTrooperCommandHandler;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Signup behavior when the event-wide, organization, and station limits all
 * apply at once. A stationed trooper goes GOING only when all three have room.
 *
 * Also documents the scope of each limit: the event-wide and organization
 * limits are enforced per shift, and the station limit counts only within its
 * own station.
 *
 * @see SignUpEventTrooperCommandHandler
 */
class SignUpEventTrooperCombinedLimitTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private Organization $other_org;
    private Event $event;
    private EventShift $shift;
    private EventShiftStation $target_station;
    private EventShiftStation $other_station;
    private EventOrganization $event_org;
    private Costume $costume;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->costume = Costume::factory()->create();

        $this->org = Organization::factory()->create();
        $this->other_org = Organization::factory()->create();

        $this->event = Event::factory()->create([
            Event::TROOPERS_ALLOWED => 5,
        ]);

        $this->event_org = EventOrganization::factory()
            ->forEvent($this->event)
            ->forOrganization($this->org)
            ->canAttend()
            ->create([
                EventOrganization::TROOPERS_ALLOWED => 2,
            ]);

        EventOrganization::factory()
            ->forEvent($this->event)
            ->forOrganization($this->other_org)
            ->canAttend()
            ->create([
                EventOrganization::TROOPERS_ALLOWED => null,
            ]);

        $this->shift = EventShift::factory()->forEvent($this->event)->create();

        $this->target_station = EventShiftStation::factory()
            ->forEventShift($this->shift)
            ->state([EventShiftStation::TROOPERS_ALLOWED => 2])
            ->create();

        $this->other_station = EventShiftStation::factory()
            ->forEventShift($this->shift)
            ->state([EventShiftStation::TROOPERS_ALLOWED => 2])
            ->create();
    }

    private function signUp(Trooper $trooper, EventShift $shift, EventShiftStation $station): void
    {
        $subject = app(SignUpEventTrooperCommandHandler::class);

        $subject(new SignUpEventTrooperCommand(
            event_shift: $shift,
            trooper: $trooper,
            added_by_trooper: $trooper,
            organization_id: $this->org->id,
            event_shift_station_id: $station->id,
            costume_id: $this->costume->id,
        ));
    }

    private function makeGoing(
        EventShift $shift,
        EventShiftStation $station,
        Organization $org,
        int $minutes_ago,
    ): EventTrooper {
        return EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper(Trooper::factory()->create())
            ->forEventShiftStation($station)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes($minutes_ago),
            ])
            ->create();
    }

    private function goingCount(EventShift $shift): int
    {
        return EventTrooper::where(EventTrooper::EVENT_SHIFT_ID, $shift->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->where(EventTrooper::IS_HANDLER, false)
            ->count();
    }

    private function orgGoingCount(EventShift $shift): int
    {
        return EventTrooper::where(EventTrooper::EVENT_SHIFT_ID, $shift->id)
            ->where(EventTrooper::ORGANIZATION_ID, $this->org->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
    }

    private function stationGoingCount(EventShiftStation $station): int
    {
        return EventTrooper::where(EventTrooper::EVENT_SHIFT_STATION_ID, $station->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
    }

    private function assertStatus(Trooper $trooper, EventTrooperStatus $status): void
    {
        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => $status->value,
        ]);
    }

    public function test_signup_all_three_limits_have_room_trooper_goes_going(): void
    {
        $this->makeGoing($this->shift, $this->target_station, $this->org, 5);

        $this->assertSame(1, $this->goingCount($this->shift), 'Event has room: 1 / 5');
        $this->assertSame(1, $this->orgGoingCount($this->shift), 'Organization has room: 1 / 2');
        $this->assertSame(1, $this->stationGoingCount($this->target_station), 'Station has room: 1 / 2');

        $trooper = Trooper::factory()->create();
        $this->signUp($trooper, $this->shift, $this->target_station);

        $this->assertStatus($trooper, EventTrooperStatus::GOING);
    }

    public function test_signup_event_full_org_and_station_have_room_trooper_goes_standby(): void
    {
        $this->event->update([Event::TROOPERS_ALLOWED => 1]);

        $this->makeGoing($this->shift, $this->other_station, $this->other_org, 5);

        $this->assertSame(1, $this->goingCount($this->shift), 'Event full: 1 / 1');
        $this->assertSame(0, $this->orgGoingCount($this->shift), 'Organization has room: 0 / 2');
        $this->assertSame(0, $this->stationGoingCount($this->target_station), 'Station has room: 0 / 2');

        $trooper = Trooper::factory()->create();
        $this->signUp($trooper, $this->shift, $this->target_station);

        $this->assertStatus($trooper, EventTrooperStatus::STAND_BY);
    }

    public function test_signup_org_full_event_and_station_have_room_trooper_goes_standby(): void
    {
        $this->makeGoing($this->shift, $this->other_station, $this->org, 6);
        $this->makeGoing($this->shift, $this->other_station, $this->org, 5);

        $this->assertSame(2, $this->goingCount($this->shift), 'Event has room: 2 / 5');
        $this->assertSame(2, $this->orgGoingCount($this->shift), 'Organization full: 2 / 2');
        $this->assertSame(0, $this->stationGoingCount($this->target_station), 'Station has room: 0 / 2');

        $trooper = Trooper::factory()->create();
        $this->signUp($trooper, $this->shift, $this->target_station);

        $this->assertStatus($trooper, EventTrooperStatus::STAND_BY);
    }

    public function test_signup_station_full_event_and_org_have_room_trooper_goes_standby(): void
    {
        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 4]);

        $this->makeGoing($this->shift, $this->target_station, $this->org, 6);
        $this->makeGoing($this->shift, $this->target_station, $this->org, 5);

        $this->assertSame(2, $this->goingCount($this->shift), 'Event has room: 2 / 5');
        $this->assertSame(2, $this->orgGoingCount($this->shift), 'Organization has room: 2 / 4');
        $this->assertSame(2, $this->stationGoingCount($this->target_station), 'Station full: 2 / 2');

        $trooper = Trooper::factory()->create();
        $this->signUp($trooper, $this->shift, $this->target_station);

        $this->assertStatus($trooper, EventTrooperStatus::STAND_BY);
    }

    // ========== CAPACITY SCOPE ==========

    public function test_event_limit_is_enforced_per_shift(): void
    {
        $this->event->update([Event::TROOPERS_ALLOWED => 1]);

        $shift_b = EventShift::factory()->forEvent($this->event)->create();
        $shift_b_station = EventShiftStation::factory()
            ->forEventShift($shift_b)
            ->state([EventShiftStation::TROOPERS_ALLOWED => 2])
            ->create();

        $this->makeGoing($this->shift, $this->target_station, $this->other_org, 5);

        $this->assertSame(1, $this->goingCount($this->shift), 'Shift A is at the event limit: 1 / 1');
        $this->assertSame(0, $this->goingCount($shift_b), 'Shift B has no GOING troopers yet');

        $trooper = Trooper::factory()->create();
        $this->signUp($trooper, $shift_b, $shift_b_station);

        $this->assertStatus($trooper, EventTrooperStatus::GOING);
        $this->assertSame(1, $this->goingCount($shift_b), 'The event-wide limit applies per shift');
    }

    public function test_org_limit_is_enforced_per_shift(): void
    {
        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 1]);

        $shift_b = EventShift::factory()->forEvent($this->event)->create();
        $shift_b_station = EventShiftStation::factory()
            ->forEventShift($shift_b)
            ->state([EventShiftStation::TROOPERS_ALLOWED => 2])
            ->create();

        $this->makeGoing($this->shift, $this->target_station, $this->org, 5);

        $this->assertSame(1, $this->orgGoingCount($this->shift), 'Shift A org count at limit: 1 / 1');
        $this->assertSame(0, $this->orgGoingCount($shift_b), 'Shift B org count empty');

        $trooper = Trooper::factory()->create();
        $this->signUp($trooper, $shift_b, $shift_b_station);

        $this->assertStatus($trooper, EventTrooperStatus::GOING);
        $this->assertSame(1, $this->orgGoingCount($shift_b), 'The organization limit applies per shift');
    }

    public function test_station_limit_counts_only_within_its_station(): void
    {
        $this->target_station->update([EventShiftStation::TROOPERS_ALLOWED => 1]);

        $this->makeGoing($this->shift, $this->target_station, $this->org, 5);

        $this->assertSame(1, $this->stationGoingCount($this->target_station), 'Target station full: 1 / 1');
        $this->assertSame(0, $this->stationGoingCount($this->other_station), 'Other station empty: 0 / 2');

        $trooper = Trooper::factory()->create();
        $this->signUp($trooper, $this->shift, $this->other_station);

        $this->assertStatus($trooper, EventTrooperStatus::GOING);
        $this->assertSame(1, $this->stationGoingCount($this->other_station), 'Only the chosen station counts');
    }
}
