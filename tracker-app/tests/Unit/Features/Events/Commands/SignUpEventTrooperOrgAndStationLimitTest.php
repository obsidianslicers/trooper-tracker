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
 * Signup behavior when both organization and station limits apply.
 *
 * A stationed trooper receives GOING only when the event limit (null =
 * unlimited), the organization limit (null = unlimited), and the required
 * numerical station limit all have room. If any applicable limit is full,
 * the trooper receives STAND_BY.
 *
 * @see SignUpEventTrooperCommandHandler
 */
class SignUpEventTrooperOrgAndStationLimitTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
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

        $this->event = Event::factory()->create([
            Event::TROOPERS_ALLOWED => null,
        ]);

        $this->event_org = EventOrganization::factory()
            ->forEvent($this->event)
            ->forOrganization($this->org)
            ->canAttend()
            ->create([
                EventOrganization::TROOPERS_ALLOWED => 2,
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

    private function signUp(Trooper $trooper, EventShiftStation $station): void
    {
        $subject = app(SignUpEventTrooperCommandHandler::class);

        $subject(new SignUpEventTrooperCommand(
            event_shift: $this->shift,
            trooper: $trooper,
            added_by_trooper: $trooper,
            organization_id: $this->org->id,
            event_shift_station_id: $station->id,
            costume_id: $this->costume->id,
        ));
    }

    private function makeGoing(EventShiftStation $station, int $minutes_ago): EventTrooper
    {
        return EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper(Trooper::factory()->create())
            ->forEventShiftStation($station)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes($minutes_ago),
            ])
            ->create();
    }

    private function orgGoingCount(): int
    {
        return EventTrooper::where(EventTrooper::ORGANIZATION_ID, $this->org->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
    }

    private function stationGoingCount(EventShiftStation $station): int
    {
        return EventTrooper::where(EventTrooper::EVENT_SHIFT_STATION_ID, $station->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
    }

    public function test_signup_both_limits_have_room_trooper_goes_going(): void
    {
        $this->assertSame(0, $this->orgGoingCount(), 'Organization empty: 0 / 2');
        $this->assertSame(0, $this->stationGoingCount($this->target_station), 'Station empty: 0 / 2');

        $trooper = Trooper::factory()->create();
        $this->signUp($trooper, $this->target_station);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::ORGANIZATION_ID => $this->org->id,
            EventTrooper::EVENT_SHIFT_STATION_ID => $this->target_station->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
        ]);
    }

    public function test_signup_org_has_room_station_full_trooper_goes_standby(): void
    {
        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 3]);

        $this->makeGoing($this->target_station, 3);
        $this->makeGoing($this->target_station, 2);

        $this->assertSame(2, $this->orgGoingCount(), 'Organization has room: 2 / 3');
        $this->assertSame(2, $this->stationGoingCount($this->target_station), 'Station full: 2 / 2');

        $trooper = Trooper::factory()->create();
        $this->signUp($trooper, $this->target_station);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::STAND_BY->value,
        ]);
        $this->assertSame(2, $this->stationGoingCount($this->target_station), 'Station stays at 2 / 2');
    }

    public function test_signup_org_full_station_has_room_trooper_goes_standby(): void
    {
        $this->makeGoing($this->target_station, 3);
        $this->makeGoing($this->other_station, 2);

        $this->assertSame(2, $this->orgGoingCount(), 'Organization full: 2 / 2');
        $this->assertSame(1, $this->stationGoingCount($this->target_station), 'Target station has room: 1 / 2');

        $trooper = Trooper::factory()->create();
        $this->signUp($trooper, $this->target_station);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::STAND_BY->value,
        ]);
        $this->assertSame(2, $this->orgGoingCount(), 'Organization stays at 2 / 2');
    }

    public function test_signup_both_limits_full_trooper_goes_standby(): void
    {
        $this->makeGoing($this->target_station, 3);
        $this->makeGoing($this->target_station, 2);

        $this->assertSame(2, $this->orgGoingCount(), 'Organization full: 2 / 2');
        $this->assertSame(2, $this->stationGoingCount($this->target_station), 'Station full: 2 / 2');

        $trooper = Trooper::factory()->create();
        $this->signUp($trooper, $this->target_station);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::STAND_BY->value,
        ]);
    }

    public function test_signup_one_spot_remains_first_gets_going_second_standby(): void
    {
        $this->makeGoing($this->target_station, 5);

        $this->assertSame(1, $this->orgGoingCount(), 'One organization spot remains: 1 / 2');
        $this->assertSame(1, $this->stationGoingCount($this->target_station), 'One station spot remains: 1 / 2');

        $second_trooper = Trooper::factory()->create();
        $this->signUp($second_trooper, $this->target_station);

        $second_status = EventTrooper::where(EventTrooper::TROOPER_ID, $second_trooper->id)
            ->toBase()
            ->value(EventTrooper::STATUS);
        $this->assertSame(EventTrooperStatus::GOING->value, $second_status);

        $third_trooper = Trooper::factory()->create();
        $this->signUp($third_trooper, $this->target_station);

        $third_status = EventTrooper::where(EventTrooper::TROOPER_ID, $third_trooper->id)
            ->toBase()
            ->value(EventTrooper::STATUS);
        $this->assertSame(EventTrooperStatus::STAND_BY->value, $third_status);

        $this->assertSame(2, $this->orgGoingCount(), 'Final organization count is exactly 2');
        $this->assertSame(2, $this->stationGoingCount($this->target_station), 'Final station count is exactly 2');
    }

    public function test_sequential_signups_for_last_spot_first_signup_wins(): void
    {
        $this->makeGoing($this->target_station, 5);

        $this->assertSame(1, $this->orgGoingCount(), 'One organization spot remains: 1 / 2');
        $this->assertSame(1, $this->stationGoingCount($this->target_station), 'One station spot remains: 1 / 2');

        $trooper_a = Trooper::factory()->create();
        $this->signUp($trooper_a, $this->target_station);

        $trooper_b = Trooper::factory()->create();
        $this->signUp($trooper_b, $this->target_station);

        $status_a = EventTrooper::where(EventTrooper::TROOPER_ID, $trooper_a->id)
            ->toBase()
            ->value(EventTrooper::STATUS);
        $status_b = EventTrooper::where(EventTrooper::TROOPER_ID, $trooper_b->id)
            ->toBase()
            ->value(EventTrooper::STATUS);

        $this->assertSame(EventTrooperStatus::GOING->value, $status_a, 'First signup gets the spot');
        $this->assertSame(EventTrooperStatus::STAND_BY->value, $status_b, 'Second signup is queued');

        $this->assertSame(2, $this->orgGoingCount(), 'Final organization count is exactly 2');
        $this->assertSame(2, $this->stationGoingCount($this->target_station), 'Final station count is exactly 2');
    }

    public function test_invariant_org_limit_never_exceeded(): void
    {
        foreach (range(1, 5) as $ignored)
        {
            $this->signUp(Trooper::factory()->create(), $this->target_station);
        }

        $this->assertSame(2, $this->orgGoingCount(), 'Organization GOING count is exactly its limit of 2');
    }

    public function test_invariant_station_limit_never_exceeded(): void
    {
        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => null]);

        foreach (range(1, 5) as $ignored)
        {
            $this->signUp(Trooper::factory()->create(), $this->target_station);
        }

        $this->assertSame(2, $this->stationGoingCount($this->target_station), 'Station GOING count is exactly its limit of 2');
    }
}
