<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Features\Events\Commands\ReconcileEventRosterCommand;
use App\Features\Events\Commands\ReconcileEventRosterCommandHandler;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Roster reconciliation after event, organization, or station limits change.
 *
 * The organization limit may be null (unlimited); stations always carry a
 * required positive numerical limit and are never unlimited. Reducing a limit
 * demotes the newest GOING troopers first; raising one promotes the oldest
 * eligible standby troopers first. Reconciliation is idempotent.
 *
 * @see ReconcileEventRosterCommandHandler
 */
class ReconcileEventRosterLimitChangeTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private Event $event;
    private EventShift $shift;
    private EventShiftStation $target_station;
    private EventShiftStation $other_station;
    private EventOrganization $event_org;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Mail::fake();

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

    private function reconcile(): void
    {
        $subject = app(ReconcileEventRosterCommandHandler::class);

        $subject(new ReconcileEventRosterCommand($this->event, Trooper::factory()->create()));
    }

    private function makeTrooper(
        EventTrooperStatus $status,
        EventShiftStation $station,
        int $minutes_ago,
    ): EventTrooper {
        return EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper(Trooper::factory()->create())
            ->forEventShiftStation($station)
            ->create([
                EventTrooper::STATUS => $status,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes($minutes_ago),
            ]);
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

    // ========== ORGANIZATION LIMIT CHANGES ==========

    public function test_org_limit_increased_standby_promoted_if_station_has_room(): void
    {
        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 1]);

        $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, 2);
        $standby_cet = $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->target_station, 1);

        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 2]);

        $this->reconcile();

        $standby_cet->refresh();
        $this->assertSame(EventTrooperStatus::GOING, $standby_cet->status, 'Oldest standby promoted');
        $this->assertSame(2, $this->orgGoingCount(), 'Organization count is exactly 2');
    }

    public function test_org_limit_increased_standby_remains_if_station_full(): void
    {
        $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, 3);
        $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, 2);
        $standby_cet = $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->target_station, 1);

        $this->assertSame(2, $this->stationGoingCount($this->target_station), 'Station full: 2 / 2');

        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 3]);

        $this->reconcile();

        $standby_cet->refresh();
        $this->assertSame(EventTrooperStatus::STAND_BY, $standby_cet->status, 'Standby blocked by full station');
        $this->assertSame(2, $this->orgGoingCount(), 'Organization count unchanged');
        $this->assertSame(2, $this->stationGoingCount($this->target_station), 'Station count unchanged');
    }

    public function test_org_limit_reduced_newest_troopers_demoted_first(): void
    {
        $this->target_station->update([EventShiftStation::TROOPERS_ALLOWED => 5]);
        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 3]);

        $oldest_cet = $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, 3);
        $middle_cet = $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, 2);
        $newest_cet = $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, 1);

        $this->assertSame(3, $this->orgGoingCount(), 'Organization full: 3 / 3');

        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 2]);

        $this->reconcile();

        $oldest_cet->refresh();
        $middle_cet->refresh();
        $newest_cet->refresh();

        $this->assertSame(EventTrooperStatus::GOING, $oldest_cet->status, 'Oldest remains GOING');
        $this->assertSame(EventTrooperStatus::GOING, $middle_cet->status, 'Middle remains GOING');
        $this->assertSame(EventTrooperStatus::STAND_BY, $newest_cet->status, 'Newest demoted first');
        $this->assertSame(2, $this->orgGoingCount(), 'Final organization count is exactly 2');
    }

    public function test_org_unlimited_standby_promoted_if_station_has_room(): void
    {
        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 1]);

        $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, 2);
        $standby_cet = $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->target_station, 1);

        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => null]);

        $this->reconcile();

        $standby_cet->refresh();
        $this->assertSame(EventTrooperStatus::GOING, $standby_cet->status, 'Standby promoted: org unlimited, station has room');
    }

    public function test_org_unlimited_station_full_standby_remains(): void
    {
        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => null]);

        $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, 3);
        $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, 2);
        $standby_cet = $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->target_station, 1);

        $this->assertSame(2, $this->stationGoingCount($this->target_station), 'Station full: 2 / 2');

        $this->reconcile();

        $standby_cet->refresh();
        $this->assertSame(
            EventTrooperStatus::STAND_BY,
            $standby_cet->status,
            'Unlimited org and event limits do not bypass the full station limit'
        );
        $this->assertSame(2, $this->stationGoingCount($this->target_station), 'Station count remains exactly 2');
    }

    // ========== STATION LIMIT CHANGES ==========

    public function test_station_limit_increased_standby_promoted_if_org_has_room(): void
    {
        $this->target_station->update([EventShiftStation::TROOPERS_ALLOWED => 1]);

        $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, 2);
        $standby_cet = $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->target_station, 1);

        $this->target_station->update([EventShiftStation::TROOPERS_ALLOWED => 2]);

        $this->reconcile();

        $standby_cet->refresh();
        $this->assertSame(EventTrooperStatus::GOING, $standby_cet->status, 'Standby promoted after limit increase');
        $this->assertSame(2, $this->stationGoingCount($this->target_station), 'Final station count is exactly 2');
    }

    public function test_station_limit_increased_org_full_standby_remains(): void
    {
        $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, 3);
        $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, 2);
        $standby_cet = $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->target_station, 1);

        $this->assertSame(2, $this->orgGoingCount(), 'Organization full: 2 / 2');

        $this->target_station->update([EventShiftStation::TROOPERS_ALLOWED => 3]);

        $this->reconcile();

        $standby_cet->refresh();
        $this->assertSame(
            EventTrooperStatus::STAND_BY,
            $standby_cet->status,
            'Increased station capacity does not bypass the full organization limit'
        );
        $this->assertSame(2, $this->orgGoingCount(), 'Organization count remains exactly 2');
    }

    public function test_station_limit_reduced_newest_troopers_demoted_first(): void
    {
        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 5]);

        $oldest_cet = $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, 2);
        $newest_cet = $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, 1);

        $this->assertSame(2, $this->stationGoingCount($this->target_station), 'Station full: 2 / 2');

        $this->target_station->update([EventShiftStation::TROOPERS_ALLOWED => 1]);

        $this->reconcile();

        $oldest_cet->refresh();
        $newest_cet->refresh();

        $this->assertSame(EventTrooperStatus::GOING, $oldest_cet->status, 'Oldest remains GOING');
        $this->assertSame(EventTrooperStatus::STAND_BY, $newest_cet->status, 'Newest demoted');
        $this->assertSame(1, $this->stationGoingCount($this->target_station), 'Final station count is exactly 1');
    }

    public function test_org_unlimited_and_station_limit_increased_promotes_eligible_standbys(): void
    {
        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 1]);
        $this->target_station->update([EventShiftStation::TROOPERS_ALLOWED => 1]);

        $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, 5);
        $standby1_cet = $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->target_station, 4);
        $standby2_cet = $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->target_station, 3);
        $cancelled_cet = $this->makeTrooper(EventTrooperStatus::CANCELLED, $this->target_station, 2);

        $record_count_before = EventTrooper::where(EventTrooper::EVENT_SHIFT_ID, $this->shift->id)->count();
        $standby1_signed_up_at = $standby1_cet->signed_up_at->copy();
        $standby2_signed_up_at = $standby2_cet->signed_up_at->copy();

        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => null]);
        $this->target_station->update([EventShiftStation::TROOPERS_ALLOWED => 2]);

        $this->reconcile();

        $standby1_cet->refresh();
        $standby2_cet->refresh();
        $cancelled_cet->refresh();

        $this->assertSame(EventTrooperStatus::GOING, $standby1_cet->status, 'Oldest standby fills the new spot');
        $this->assertSame(
            EventTrooperStatus::STAND_BY,
            $standby2_cet->status,
            'Promotion stops at the station numerical capacity'
        );
        $this->assertSame(EventTrooperStatus::CANCELLED, $cancelled_cet->status, 'Cancelled stays cancelled');

        $record_count_after = EventTrooper::where(EventTrooper::EVENT_SHIFT_ID, $this->shift->id)->count();
        $this->assertSame($record_count_before, $record_count_after, 'No duplicate records created');

        $station_going = $this->stationGoingCount($this->target_station);
        $this->assertSame(2, $station_going, 'Station GOING count equals its limit');
        $this->assertLessThanOrEqual(
            $this->target_station->fresh()->troopers_allowed,
            $station_going,
            'Station GOING count never exceeds its numerical limit'
        );

        $this->assertTrue($standby1_cet->signed_up_at->equalTo($standby1_signed_up_at));
        $this->assertTrue($standby2_cet->signed_up_at->equalTo($standby2_signed_up_at));
    }

    // ========== IDEMPOTENCY & INVARIANTS ==========

    public function test_reconciliation_is_idempotent(): void
    {
        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 1]);

        $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, 3);
        $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, 2);
        $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->target_station, 1);

        $columns = [
            EventTrooper::ID,
            EventTrooper::TROOPER_ID,
            EventTrooper::STATUS,
            EventTrooper::SIGNED_UP_AT,
            EventTrooper::ORGANIZATION_ID,
            EventTrooper::EVENT_SHIFT_STATION_ID,
        ];

        $signed_up_at_before = EventTrooper::query()
            ->where(EventTrooper::EVENT_SHIFT_ID, $this->shift->id)
            ->orderBy(EventTrooper::ID)
            ->pluck(EventTrooper::SIGNED_UP_AT, EventTrooper::ID)
            ->toArray();

        $this->reconcile();

        $state_after_first = EventTrooper::query()
            ->where(EventTrooper::EVENT_SHIFT_ID, $this->shift->id)
            ->orderBy(EventTrooper::ID)
            ->get($columns)
            ->toArray();

        $this->reconcile();

        $state_after_second = EventTrooper::query()
            ->where(EventTrooper::EVENT_SHIFT_ID, $this->shift->id)
            ->orderBy(EventTrooper::ID)
            ->get($columns)
            ->toArray();

        $this->assertSame($state_after_first, $state_after_second, 'Second run changes nothing');

        $signed_up_at_after = EventTrooper::query()
            ->where(EventTrooper::EVENT_SHIFT_ID, $this->shift->id)
            ->orderBy(EventTrooper::ID)
            ->pluck(EventTrooper::SIGNED_UP_AT, EventTrooper::ID)
            ->toArray();

        $this->assertEquals(
            $signed_up_at_before,
            $signed_up_at_after,
            'Reconciliation never rewrites signed_up_at'
        );
    }

    public function test_cancelled_troopers_are_excluded_and_never_promoted(): void
    {
        $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, 3);
        $cancelled_cet = $this->makeTrooper(EventTrooperStatus::CANCELLED, $this->target_station, 2);
        $standby_cet = $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->target_station, 1);

        $this->reconcile();

        $cancelled_cet->refresh();
        $standby_cet->refresh();

        $this->assertSame(EventTrooperStatus::CANCELLED, $cancelled_cet->status, 'Cancelled never promoted');
        $this->assertSame(
            EventTrooperStatus::GOING,
            $standby_cet->status,
            'Cancelled troopers do not count toward capacity'
        );
    }
}
