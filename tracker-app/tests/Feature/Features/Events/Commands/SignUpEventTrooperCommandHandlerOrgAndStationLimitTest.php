<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Features\Events\Commands\SignUpEventTrooperCommand;
use App\Features\Events\Commands\SignUpEventTrooperCommandHandler;
use App\Jobs\ReconcileEventRosterJob;
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
 * Comprehensive tests for signup behavior when both organization and station limits apply.
 *
 * A trooper receives GOING only when both the organization limit and station limit have room.
 * If either limit is full, the trooper is assigned STAND_BY.
 *
 * Reconciliation must respect both limits and maintain deterministic queue order:
 * signed_up_at ASC, then event_trooper.id ASC as tie-breaker.
 *
 * @see SignUpEventTrooperCommandHandler
 */
class SignUpEventTrooperCommandHandlerOrgAndStationLimitTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private Event $event;
    private EventShift $shift;
    private EventShiftStation $targetStation;
    private EventShiftStation $otherStation;
    private EventOrganization $eventOrg;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->org = Organization::factory()->create();

        $this->event = Event::factory()->create([
            Event::TROOPERS_ALLOWED => null,
        ]);

        $this->eventOrg = EventOrganization::factory()
            ->forEvent($this->event)
            ->forOrganization($this->org)
            ->canAttend()
            ->create([
                EventOrganization::TROOPERS_ALLOWED => 2,
            ]);

        $this->shift = EventShift::factory()->forEvent($this->event)->create();

        $this->targetStation = EventShiftStation::factory()
            ->forEventShift($this->shift)
            ->state([EventShiftStation::TROOPERS_ALLOWED => 2])
            ->create();

        $this->otherStation = EventShiftStation::factory()
            ->forEventShift($this->shift)
            ->state([EventShiftStation::TROOPERS_ALLOWED => 2])
            ->create();
    }

    // ========== SIGNUP SCENARIOS ==========

    public function test_signup_both_limits_have_room_trooper_goes_going(): void
    {
        $trooper = Trooper::factory()->create();

        app(SignUpEventTrooperCommandHandler::class)(new SignUpEventTrooperCommand(
            event_shift: $this->shift,
            trooper: $trooper,
            added_by_trooper: $trooper,
            organization_id: $this->org->id,
            event_shift_station_id: $this->targetStation->id,
        ));

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::ORGANIZATION_ID => $this->org->id,
            EventTrooper::EVENT_SHIFT_STATION_ID => $this->targetStation->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
        ]);
    }

    public function test_signup_org_has_room_station_full_trooper_goes_standby(): void
    {
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 3]);

        $firstTrooper = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($firstTrooper)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
            ])
            ->create();

        $secondTrooper = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($secondTrooper)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
            ])
            ->create();

        $orgGoing = EventTrooper::where(EventTrooper::ORGANIZATION_ID, $this->org->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
        $this->assertSame(2, $orgGoing, 'Organization has room: 2 / 3');

        $targetStationGoing = EventTrooper::where(EventTrooper::EVENT_SHIFT_STATION_ID, $this->targetStation->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
        $this->assertSame(2, $targetStationGoing, 'Station is full: 2 / 2');

        $newTrooper = Trooper::factory()->create();

        app(SignUpEventTrooperCommandHandler::class)(new SignUpEventTrooperCommand(
            event_shift: $this->shift,
            trooper: $newTrooper,
            added_by_trooper: $newTrooper,
            organization_id: $this->org->id,
            event_shift_station_id: $this->targetStation->id,
        ));

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $newTrooper->id,
            EventTrooper::ORGANIZATION_ID => $this->org->id,
            EventTrooper::EVENT_SHIFT_STATION_ID => $this->targetStation->id,
            EventTrooper::STATUS => EventTrooperStatus::STAND_BY->value,
        ]);
    }

    public function test_signup_org_full_station_has_room_trooper_goes_standby(): void
    {
        $firstOrgTrooper = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($firstOrgTrooper)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
            ])
            ->create();

        $secondOrgTrooper = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($secondOrgTrooper)
            ->forEventShiftStation($this->otherStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
            ])
            ->create();

        $newTrooper = Trooper::factory()->create();

        app(SignUpEventTrooperCommandHandler::class)(new SignUpEventTrooperCommand(
            event_shift: $this->shift,
            trooper: $newTrooper,
            added_by_trooper: $newTrooper,
            organization_id: $this->org->id,
            event_shift_station_id: $this->targetStation->id,
        ));

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $newTrooper->id,
            EventTrooper::ORGANIZATION_ID => $this->org->id,
            EventTrooper::EVENT_SHIFT_STATION_ID => $this->targetStation->id,
            EventTrooper::STATUS => EventTrooperStatus::STAND_BY->value,
        ]);

        $orgGoing = EventTrooper::where(EventTrooper::ORGANIZATION_ID, $this->org->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();

        $this->assertSame(2, $orgGoing, 'Organization at capacity');
    }

    public function test_signup_both_limits_full_trooper_goes_standby(): void
    {
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
            ])
            ->create();

        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
            ])
            ->create();

        $newTrooper = Trooper::factory()->create();

        app(SignUpEventTrooperCommandHandler::class)(new SignUpEventTrooperCommand(
            event_shift: $this->shift,
            trooper: $newTrooper,
            added_by_trooper: $newTrooper,
            organization_id: $this->org->id,
            event_shift_station_id: $this->targetStation->id,
        ));

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $newTrooper->id,
            EventTrooper::STATUS => EventTrooperStatus::STAND_BY->value,
        ]);
    }

    public function test_signup_exactly_one_spot_remains_first_gets_going_second_standby(): void
    {
        $firstTrooper = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($firstTrooper)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinute(),
            ])
            ->create();

        $secondTrooper = Trooper::factory()->create();

        app(SignUpEventTrooperCommandHandler::class)(new SignUpEventTrooperCommand(
            event_shift: $this->shift,
            trooper: $secondTrooper,
            added_by_trooper: $secondTrooper,
            organization_id: $this->org->id,
            event_shift_station_id: $this->targetStation->id,
        ));

        $secondStatus = EventTrooper::where(EventTrooper::TROOPER_ID, $secondTrooper->id)
            ->toBase()
            ->value(EventTrooper::STATUS);

        $this->assertSame(EventTrooperStatus::GOING->value, $secondStatus);

        $thirdTrooper = Trooper::factory()->create();

        app(SignUpEventTrooperCommandHandler::class)(new SignUpEventTrooperCommand(
            event_shift: $this->shift,
            trooper: $thirdTrooper,
            added_by_trooper: $thirdTrooper,
            organization_id: $this->org->id,
            event_shift_station_id: $this->targetStation->id,
        ));

        $thirdStatus = EventTrooper::where(EventTrooper::TROOPER_ID, $thirdTrooper->id)
            ->toBase()
            ->value(EventTrooper::STATUS);

        $this->assertSame(EventTrooperStatus::STAND_BY->value, $thirdStatus);

        $orgGoing = EventTrooper::where(EventTrooper::ORGANIZATION_ID, $this->org->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();

        $this->assertSame(2, $orgGoing, 'Organization limit is exactly 2');

        $stationGoing = EventTrooper::where(EventTrooper::EVENT_SHIFT_STATION_ID, $this->targetStation->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();

        $this->assertSame(2, $stationGoing, 'Station limit is exactly 2');
    }

    public function test_sequential_signups_for_last_spot_first_signup_wins(): void
    {
        $existingTrooper = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($existingTrooper)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
            ])
            ->create();

        $orgGoing = EventTrooper::where(EventTrooper::ORGANIZATION_ID, $this->org->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
        $this->assertSame(1, $orgGoing, 'Organization has 1 available spot before signups');

        $stationGoing = EventTrooper::where(EventTrooper::EVENT_SHIFT_STATION_ID, $this->targetStation->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
        $this->assertSame(1, $stationGoing, 'Station has 1 available spot before signups');

        $secondTrooper = Trooper::factory()->create();
        app(SignUpEventTrooperCommandHandler::class)(new SignUpEventTrooperCommand(
            event_shift: $this->shift,
            trooper: $secondTrooper,
            added_by_trooper: $secondTrooper,
            organization_id: $this->org->id,
            event_shift_station_id: $this->targetStation->id,
        ));

        $thirdTrooper = Trooper::factory()->create();
        app(SignUpEventTrooperCommandHandler::class)(new SignUpEventTrooperCommand(
            event_shift: $this->shift,
            trooper: $thirdTrooper,
            added_by_trooper: $thirdTrooper,
            organization_id: $this->org->id,
            event_shift_station_id: $this->targetStation->id,
        ));

        $finalOrgGoing = EventTrooper::where(EventTrooper::ORGANIZATION_ID, $this->org->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
        $this->assertSame(2, $finalOrgGoing, 'Organization limit is exactly 2');

        $finalStationGoing = EventTrooper::where(EventTrooper::EVENT_SHIFT_STATION_ID, $this->targetStation->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
        $this->assertSame(2, $finalStationGoing, 'Station limit is exactly 2');

        $secondStatus = EventTrooper::where(EventTrooper::TROOPER_ID, $secondTrooper->id)
            ->toBase()
            ->value(EventTrooper::STATUS);
        $thirdStatus = EventTrooper::where(EventTrooper::TROOPER_ID, $thirdTrooper->id)
            ->toBase()
            ->value(EventTrooper::STATUS);

        $this->assertSame(EventTrooperStatus::GOING->value, $secondStatus, 'First signup gets the available spot');
        $this->assertSame(EventTrooperStatus::STAND_BY->value, $thirdStatus, 'Second signup is queued');
    }

    // ========== RECONCILIATION: ORGANIZATION LIMIT CHANGES ==========

    public function test_reconcile_org_limit_increased_standby_promoted_if_station_has_room(): void
    {
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 1]);

        $goingTrooper = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($goingTrooper)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
            ])
            ->create();

        $standbyTrooper = Trooper::factory()->create();
        $standbyCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($standbyTrooper)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
            ]);

        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 2]);

        (new ReconcileEventRosterJob($this->event, Trooper::factory()->create()))->handle();

        $standbyCet->refresh();
        $this->assertSame(EventTrooperStatus::GOING, $standbyCet->status);
    }

    public function test_reconcile_org_limit_increased_standby_remains_if_station_full(): void
    {
        $going1 = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($going1)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(2),
            ])
            ->create();

        $going2 = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($going2)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinute(),
            ])
            ->create();

        $standbyCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now(),
            ]);

        $stationGoing = EventTrooper::where(EventTrooper::EVENT_SHIFT_STATION_ID, $this->targetStation->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
        $this->assertSame(2, $stationGoing, 'Station is full before reconciliation');

        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 3]);

        (new ReconcileEventRosterJob($this->event, Trooper::factory()->create()))->handle();

        $standbyCet->refresh();
        $this->assertSame(EventTrooperStatus::STAND_BY, $standbyCet->status, 'Standby remains because station is full');

        $finalOrgGoing = EventTrooper::where(EventTrooper::ORGANIZATION_ID, $this->org->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
        $this->assertSame(2, $finalOrgGoing, 'Organization count unchanged');

        $finalStationGoing = EventTrooper::where(EventTrooper::EVENT_SHIFT_STATION_ID, $this->targetStation->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
        $this->assertSame(2, $finalStationGoing, 'Station count unchanged');
    }

    public function test_reconcile_org_limit_reduced_newest_troopers_demoted_first(): void
    {
        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 5]);
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 3]);

        $oldestTrooper = Trooper::factory()->create();
        $oldestCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($oldestTrooper)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(3),
            ])
            ->create();

        $middleTrooper = Trooper::factory()->create();
        $middleCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($middleTrooper)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(2),
            ])
            ->create();

        $newestTrooper = Trooper::factory()->create();
        $newestCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($newestTrooper)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now(),
            ])
            ->create();

        $this->assertSame(3, EventTrooper::where(EventTrooper::ORGANIZATION_ID, $this->org->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count(), 'Organization: 3 / 3 before reduction');

        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 2]);

        (new ReconcileEventRosterJob($this->event, Trooper::factory()->create()))->handle();

        $oldestCet->refresh();
        $middleCet->refresh();
        $newestCet->refresh();

        $this->assertSame(EventTrooperStatus::GOING, $oldestCet->status, 'Oldest trooper remains GOING');
        $this->assertSame(EventTrooperStatus::GOING, $middleCet->status, 'Middle trooper remains GOING');
        $this->assertSame(EventTrooperStatus::STAND_BY, $newestCet->status, 'Newest trooper demoted to STAND_BY');

        $orgGoing = EventTrooper::where(EventTrooper::ORGANIZATION_ID, $this->org->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();

        $this->assertSame(2, $orgGoing, 'Organization: 2 / 2 after reduction');
    }

    public function test_reconcile_org_limit_unlimited_standby_promoted_if_station_has_room(): void
    {
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 1]);

        $going = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($going)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
            ])
            ->create();

        $standby = Trooper::factory()->create();
        $standbyCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($standby)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
            ]);

        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => null]);

        (new ReconcileEventRosterJob($this->event, Trooper::factory()->create()))->handle();

        $standbyCet->refresh();
        $this->assertSame(EventTrooperStatus::GOING, $standbyCet->status, 'Standby promoted when org limit is removed');
    }

    // ========== RECONCILIATION: STATION LIMIT CHANGES ==========

    public function test_reconcile_station_limit_increased_standby_promoted_if_org_has_room(): void
    {
        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 1]);

        $going = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($going)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
            ])
            ->create();

        $standby = Trooper::factory()->create();
        $standbyCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($standby)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
            ]);

        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 2]);

        (new ReconcileEventRosterJob($this->event, Trooper::factory()->create()))->handle();

        $standbyCet->refresh();
        $this->assertSame(EventTrooperStatus::GOING, $standbyCet->status);

        $finalStationGoing = EventTrooper::where(EventTrooper::EVENT_SHIFT_STATION_ID, $this->targetStation->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
        $this->assertSame(2, $finalStationGoing, 'Station count is exactly 2');
    }

    public function test_reconcile_station_limit_increased_standby_remains_if_org_full(): void
    {
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 1]);
        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 1]);

        $going = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($going)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinute(),
            ])
            ->create();

        $standby = Trooper::factory()->create();
        $standbyCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($standby)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now(),
            ]);

        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 2]);

        (new ReconcileEventRosterJob($this->event, Trooper::factory()->create()))->handle();

        $standbyCet->refresh();
        $this->assertSame(EventTrooperStatus::STAND_BY, $standbyCet->status, 'Standby remains because org is full');

        $finalOrgGoing = EventTrooper::where(EventTrooper::ORGANIZATION_ID, $this->org->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
        $this->assertSame(1, $finalOrgGoing, 'Organization remains at limit');
    }

    public function test_reconcile_station_limit_reduced_newest_troopers_demoted(): void
    {
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 5]);
        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 2]);

        $oldest = Trooper::factory()->create();
        $oldestCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($oldest)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinute(),
            ])
            ->create();

        $newest = Trooper::factory()->create();
        $newestCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($newest)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now(),
            ])
            ->create();

        $initialStationGoing = EventTrooper::where(EventTrooper::EVENT_SHIFT_STATION_ID, $this->targetStation->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
        $this->assertSame(2, $initialStationGoing, 'Station: 2 / 2 before reduction');

        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 1]);

        (new ReconcileEventRosterJob($this->event, Trooper::factory()->create()))->handle();

        $oldestCet->refresh();
        $newestCet->refresh();

        $this->assertSame(EventTrooperStatus::GOING, $oldestCet->status, 'Oldest trooper remains GOING');
        $this->assertSame(EventTrooperStatus::STAND_BY, $newestCet->status, 'Newest trooper demoted');

        $finalStationGoing = EventTrooper::where(EventTrooper::EVENT_SHIFT_STATION_ID, $this->targetStation->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
        $this->assertSame(1, $finalStationGoing, 'Station: 1 / 1 after reduction');
    }

    public function test_reconcile_station_limit_increased_to_accommodate_standby(): void
    {
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 3]);
        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 2]);

        $going1 = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($going1)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(2),
            ])
            ->create();

        $going2 = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($going2)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinute(),
            ])
            ->create();

        $standby = Trooper::factory()->create();
        $standbyCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($standby)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now(),
            ]);

        $stationGoing = EventTrooper::where(EventTrooper::EVENT_SHIFT_STATION_ID, $this->targetStation->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
        $this->assertSame(2, $stationGoing, 'Station: 2 / 2 before limit increase');

        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 3]);

        (new ReconcileEventRosterJob($this->event, Trooper::factory()->create()))->handle();

        $standbyCet->refresh();
        $this->assertSame(EventTrooperStatus::GOING, $standbyCet->status, 'Standby promoted when station limit increased');
    }

    // ========== FAIR QUEUE ORDERING: SKIP-INELIGIBLE SCENARIO ==========

    public function test_reconcile_skips_ineligible_standby_and_promotes_later_eligible_trooper(): void
    {
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 3]);
        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 1]);
        $this->otherStation->update([EventShiftStation::TROOPERS_ALLOWED => 2]);

        $goingAtTarget = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($goingAtTarget)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(3),
            ])
            ->create();

        $goingAtOther = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($goingAtOther)
            ->forEventShiftStation($this->otherStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(2),
            ])
            ->create();

        $oldestStandby = Trooper::factory()->create();
        $oldestCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($oldestStandby)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinute(),
            ]);

        $newerStandby = Trooper::factory()->create();
        $newerCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($newerStandby)
            ->forEventShiftStation($this->otherStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now(),
            ]);

        $organizationGoing = EventTrooper::where(EventTrooper::ORGANIZATION_ID, $this->org->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
        $targetStationGoing = EventTrooper::where(EventTrooper::EVENT_SHIFT_STATION_ID, $this->targetStation->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
        $otherStationGoing = EventTrooper::where(EventTrooper::EVENT_SHIFT_STATION_ID, $this->otherStation->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();

        $this->assertSame(2, $organizationGoing, 'Organization has one available spot: 2 / 3');
        $this->assertSame(1, $targetStationGoing, 'Target station is full: 1 / 1');
        $this->assertSame(1, $otherStationGoing, 'Other station has one available spot: 1 / 2');

        $originalSignedUpAt = $oldestCet->signed_up_at->copy();

        (new ReconcileEventRosterJob($this->event, Trooper::factory()->create()))->handle();

        $oldestCet->refresh();
        $newerCet->refresh();

        $this->assertSame(EventTrooperStatus::STAND_BY, $oldestCet->status, 'Oldest stays STAND_BY (station full)');
        $this->assertSame(EventTrooperStatus::GOING, $newerCet->status, 'Newer at different station promoted');

        $this->assertSame(3, EventTrooper::where(EventTrooper::ORGANIZATION_ID, $this->org->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count(), 'Organization is exactly 3 / 3');
        $this->assertSame(1, EventTrooper::where(EventTrooper::EVENT_SHIFT_STATION_ID, $this->targetStation->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count(), 'Target station remains exactly 1 / 1');
        $this->assertSame(2, EventTrooper::where(EventTrooper::EVENT_SHIFT_STATION_ID, $this->otherStation->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count(), 'Other station is exactly 2 / 2');

        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 2]);
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 4]);

        (new ReconcileEventRosterJob($this->event, Trooper::factory()->create()))->handle();

        $oldestCet->refresh();
        $newerCet->refresh();

        $this->assertSame(EventTrooperStatus::GOING, $oldestCet->status, 'Oldest promoted after station gets room');
        $this->assertSame(EventTrooperStatus::GOING, $newerCet->status, 'Newer remains GOING');
        $this->assertTrue(
            $oldestCet->signed_up_at->equalTo($originalSignedUpAt),
            'Oldest signup time preserved'
        );
    }

    public function test_reconcile_skipped_standby_retains_queue_priority_over_newer_standby(): void
    {
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 3]);
        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 1]);
        $this->otherStation->update([EventShiftStation::TROOPERS_ALLOWED => 2]);

        $goingAtTarget = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($goingAtTarget)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(5),
            ])
            ->create();

        $goingAtOther = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($goingAtOther)
            ->forEventShiftStation($this->otherStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(4),
            ])
            ->create();

        $skippedStandby = Trooper::factory()->create();
        $skippedCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($skippedStandby)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(3),
            ]);

        $promotedStandby = Trooper::factory()->create();
        $promotedCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($promotedStandby)
            ->forEventShiftStation($this->otherStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(2),
            ]);

        $originalSignedUpAt = $skippedCet->signed_up_at->copy();

        (new ReconcileEventRosterJob($this->event, Trooper::factory()->create()))->handle();

        $skippedCet->refresh();
        $promotedCet->refresh();

        $this->assertSame(EventTrooperStatus::STAND_BY, $skippedCet->status, 'Oldest skipped (station full)');
        $this->assertSame(EventTrooperStatus::GOING, $promotedCet->status, 'Later eligible trooper promoted');

        $newestStandby = Trooper::factory()->create();
        $newestCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($newestStandby)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now(),
            ]);

        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 2]);
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 4]);

        (new ReconcileEventRosterJob($this->event, Trooper::factory()->create()))->handle();

        $skippedCet->refresh();
        $promotedCet->refresh();
        $newestCet->refresh();

        $this->assertSame(EventTrooperStatus::GOING, $skippedCet->status, 'Skipped standby promoted first');
        $this->assertSame(EventTrooperStatus::GOING, $promotedCet->status, 'Earlier promotion is kept');
        $this->assertSame(EventTrooperStatus::STAND_BY, $newestCet->status, 'Newest standby waits for next spot');
        $this->assertTrue(
            $skippedCet->signed_up_at->equalTo($originalSignedUpAt),
            'Skipped standby keeps original signup time'
        );
    }

    // ========== QUEUE ORDER: EQUAL TIMESTAMP TIE-BREAKER ==========

    public function test_reconcile_equal_signup_time_uses_id_tie_breaker(): void
    {
        $signedUpAt = now();

        $first = Trooper::factory()->create();
        $firstCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($first)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => $signedUpAt,
            ]);

        $second = Trooper::factory()->create();
        $secondCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($second)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => $signedUpAt,
            ]);

        $this->assertTrue(
            $firstCet->id < $secondCet->id,
            'First-created event trooper must have the lower ID'
        );

        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 1]);
        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 1]);

        $organizationGoing = EventTrooper::where(EventTrooper::ORGANIZATION_ID, $this->org->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
        $stationGoing = EventTrooper::where(EventTrooper::EVENT_SHIFT_STATION_ID, $this->targetStation->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();

        $this->assertSame(0, $organizationGoing, 'Organization has exactly one available spot: 0 / 1');
        $this->assertSame(0, $stationGoing, 'Station has exactly one available spot: 0 / 1');

        (new ReconcileEventRosterJob($this->event, Trooper::factory()->create()))->handle();

        $firstCet->refresh();
        $secondCet->refresh();

        $this->assertSame(EventTrooperStatus::GOING, $firstCet->status, 'Lower ID promoted when times equal');
        $this->assertSame(EventTrooperStatus::STAND_BY, $secondCet->status, 'Higher ID remains STAND_BY');

        $finalOrgGoing = EventTrooper::where(EventTrooper::ORGANIZATION_ID, $this->org->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
        $finalStationGoing = EventTrooper::where(EventTrooper::EVENT_SHIFT_STATION_ID, $this->targetStation->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();

        $this->assertSame(1, $finalOrgGoing, 'Final organization count is exactly 1');
        $this->assertSame(1, $finalStationGoing, 'Final station count is exactly 1');
    }

    // ========== INVARIANT & IDEMPOTENCY TESTS ==========

    public function test_invariant_org_limit_never_exceeded(): void
    {
        $troopers = collect(range(1, 5))
            ->map(fn () => Trooper::factory()->create())
            ->all();

        foreach ($troopers as $trooper) {
            app(SignUpEventTrooperCommandHandler::class)(new SignUpEventTrooperCommand(
                event_shift: $this->shift,
                trooper: $trooper,
                added_by_trooper: $trooper,
                organization_id: $this->org->id,
                event_shift_station_id: $this->targetStation->id,
            ));
        }

        $orgGoing = EventTrooper::where(EventTrooper::ORGANIZATION_ID, $this->org->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();

        $this->assertSame(2, $orgGoing, 'Organization limit is exactly 2');
    }

    public function test_invariant_station_limit_never_exceeded(): void
    {
        $troopers = collect(range(1, 5))
            ->map(fn () => Trooper::factory()->create())
            ->all();

        foreach ($troopers as $trooper) {
            app(SignUpEventTrooperCommandHandler::class)(new SignUpEventTrooperCommand(
                event_shift: $this->shift,
                trooper: $trooper,
                added_by_trooper: $trooper,
                organization_id: $this->org->id,
                event_shift_station_id: $this->targetStation->id,
            ));
        }

        $stationGoing = EventTrooper::where(EventTrooper::EVENT_SHIFT_STATION_ID, $this->targetStation->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();

        $this->assertSame(2, $stationGoing, 'Station limit is exactly 2');
    }

    public function test_invariant_reconciliation_is_idempotent(): void
    {
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 1]);

        $trooper1 = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($trooper1)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
            ])
            ->create();

        $trooper2 = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($trooper2)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
            ])
            ->create();

        $signedUpAtBeforeFirstRun = EventTrooper::query()
            ->where(EventTrooper::EVENT_SHIFT_ID, $this->shift->id)
            ->orderBy(EventTrooper::ID)
            ->pluck(EventTrooper::SIGNED_UP_AT, EventTrooper::ID)
            ->toArray();

        $job = new ReconcileEventRosterJob($this->event, Trooper::factory()->create());
        $job->handle();

        $stateAfterFirst = EventTrooper::query()
            ->where(EventTrooper::EVENT_SHIFT_ID, $this->shift->id)
            ->orderBy(EventTrooper::ID)
            ->get([
                EventTrooper::ID,
                EventTrooper::TROOPER_ID,
                EventTrooper::STATUS,
                EventTrooper::SIGNED_UP_AT,
                EventTrooper::ORGANIZATION_ID,
                EventTrooper::EVENT_SHIFT_STATION_ID,
            ])
            ->toArray();

        $job->handle();

        $stateAfterSecond = EventTrooper::query()
            ->where(EventTrooper::EVENT_SHIFT_ID, $this->shift->id)
            ->orderBy(EventTrooper::ID)
            ->get([
                EventTrooper::ID,
                EventTrooper::TROOPER_ID,
                EventTrooper::STATUS,
                EventTrooper::SIGNED_UP_AT,
                EventTrooper::ORGANIZATION_ID,
                EventTrooper::EVENT_SHIFT_STATION_ID,
            ])
            ->toArray();

        $this->assertSame($stateAfterFirst, $stateAfterSecond, 'Reconciliation is idempotent');

        $signedUpAtAfterSecondRun = EventTrooper::query()
            ->where(EventTrooper::EVENT_SHIFT_ID, $this->shift->id)
            ->orderBy(EventTrooper::ID)
            ->pluck(EventTrooper::SIGNED_UP_AT, EventTrooper::ID)
            ->toArray();

        $this->assertEquals(
            $signedUpAtBeforeFirstRun,
            $signedUpAtAfterSecondRun,
            'Reconciliation never rewrites signed_up_at'
        );
    }

    public function test_invariant_reconciliation_excludes_cancelled_troopers(): void
    {
        $going = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($going)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
            ])
            ->create();

        $cancelled = Trooper::factory()->create();
        $cancelledCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($cancelled)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::CANCELLED,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
            ]);

        $standby = Trooper::factory()->create();
        $standbyCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($standby)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
            ]);

        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 2]);

        (new ReconcileEventRosterJob($this->event, Trooper::factory()->create()))->handle();

        $cancelledCet->refresh();
        $standbyCet->refresh();

        $this->assertSame(EventTrooperStatus::CANCELLED, $cancelledCet->status, 'Cancelled trooper remains CANCELLED');
        $this->assertSame(EventTrooperStatus::GOING, $standbyCet->status, 'Standby promoted (cancelled not reconsidered)');
    }

    // ========== CANCELLATION WORKFLOW ==========

    private function cancelThroughWorkflow(EventTrooper $event_trooper): void
    {
        $response = $this->actingAs($event_trooper->trooper)->post(
            route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]),
            ['status' => EventTrooperStatus::CANCELLED->value],
        );

        $response->assertOk();
    }

    private function activeTrooper(): Trooper
    {
        return Trooper::factory()->asActive()->withVerifiedEmail()->create();
    }

    public function test_cancellation_opens_both_limits_oldest_standby_promoted(): void
    {
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 1]);
        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 1]);

        $canceler = $this->activeTrooper();
        $cancelerCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($canceler)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(3),
            ])
            ->create();

        $standby = Trooper::factory()->create();
        $standbyCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($standby)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(2),
            ]);

        $this->cancelThroughWorkflow($cancelerCet);

        $cancelerCet->refresh();
        $standbyCet->refresh();

        $this->assertSame(EventTrooperStatus::CANCELLED, $cancelerCet->status, 'Canceler is CANCELLED');
        $this->assertSame(EventTrooperStatus::GOING, $standbyCet->status, 'Oldest eligible standby promoted');
    }

    public function test_cancellation_opens_station_but_standby_org_remains_full(): void
    {
        $orgB = Organization::factory()->create();
        EventOrganization::factory()
            ->forEvent($this->event)
            ->forOrganization($orgB)
            ->canAttend()
            ->create([
                EventOrganization::TROOPERS_ALLOWED => 1,
            ]);

        $orgBGoing = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($orgBGoing)
            ->forEventShiftStation($this->otherStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $orgB->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(4),
            ])
            ->create();

        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 1]);

        $canceler = $this->activeTrooper();
        $cancelerCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($canceler)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(3),
            ])
            ->create();

        $standby = Trooper::factory()->create();
        $standbyCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($standby)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $orgB->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(2),
            ]);

        $this->cancelThroughWorkflow($cancelerCet);

        $cancelerCet->refresh();
        $standbyCet->refresh();

        $this->assertSame(EventTrooperStatus::CANCELLED, $cancelerCet->status, 'Canceler is CANCELLED');
        $this->assertSame(
            EventTrooperStatus::STAND_BY,
            $standbyCet->status,
            'Standby remains STAND_BY because their organization is still full'
        );

        $orgBGoingCount = EventTrooper::where(EventTrooper::ORGANIZATION_ID, $orgB->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
        $this->assertSame(1, $orgBGoingCount, 'Organization B stays at its limit of 1');
    }

    public function test_cancellation_opens_org_but_standby_station_remains_full(): void
    {
        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 1]);
        $this->otherStation->update([EventShiftStation::TROOPERS_ALLOWED => 1]);

        $goingAtTarget = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($goingAtTarget)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(5),
            ])
            ->create();

        $canceler = $this->activeTrooper();
        $cancelerCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($canceler)
            ->forEventShiftStation($this->otherStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(4),
            ])
            ->create();

        $standbyAtFullStation = Trooper::factory()->create();
        $standbyAtFullStationCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($standbyAtFullStation)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(3),
            ]);

        $standbyAtOpenedStation = Trooper::factory()->create();
        $standbyAtOpenedStationCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($standbyAtOpenedStation)
            ->forEventShiftStation($this->otherStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(2),
            ]);

        $this->cancelThroughWorkflow($cancelerCet);

        $cancelerCet->refresh();
        $standbyAtFullStationCet->refresh();
        $standbyAtOpenedStationCet->refresh();

        $this->assertSame(EventTrooperStatus::CANCELLED, $cancelerCet->status, 'Canceler is CANCELLED');
        $this->assertSame(
            EventTrooperStatus::STAND_BY,
            $standbyAtFullStationCet->status,
            'Standby at the still-full station remains STAND_BY'
        );
        $this->assertSame(
            EventTrooperStatus::GOING,
            $standbyAtOpenedStationCet->status,
            'Later standby at the opened station is promoted'
        );
    }

    public function test_standby_cancellation_preserves_remaining_queue(): void
    {
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 1]);
        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 1]);

        $going = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($going)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(3),
            ])
            ->create();

        $cancelingStandby = $this->activeTrooper();
        $cancelingStandbyCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($cancelingStandby)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(2),
            ]);

        $remainingStandby = Trooper::factory()->create();
        $remainingStandbyCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($remainingStandby)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinute(),
            ]);

        $cancelingSignedUpAt = $cancelingStandbyCet->signed_up_at->copy();
        $remainingSignedUpAt = $remainingStandbyCet->signed_up_at->copy();

        $this->cancelThroughWorkflow($cancelingStandbyCet);

        $cancelingStandbyCet->refresh();
        $remainingStandbyCet->refresh();

        $this->assertSame(EventTrooperStatus::CANCELLED, $cancelingStandbyCet->status, 'Canceled standby is CANCELLED');
        $this->assertSame(EventTrooperStatus::STAND_BY, $remainingStandbyCet->status, 'Remaining standby unchanged');
        $this->assertTrue(
            $cancelingStandbyCet->signed_up_at->equalTo($cancelingSignedUpAt),
            'Canceled standby signup time unchanged'
        );
        $this->assertTrue(
            $remainingStandbyCet->signed_up_at->equalTo($remainingSignedUpAt),
            'Remaining standby signup time unchanged'
        );
        $this->assertTrue(
            $remainingStandbyCet->signed_up_at->greaterThan($cancelingStandbyCet->signed_up_at),
            'Remaining relative queue order preserved'
        );
    }

    // ========== UNLIMITED LIMITS ==========

    public function test_reconcile_both_limits_unlimited_promotes_all_eligible_standbys(): void
    {
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 1]);
        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 1]);

        $going = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($going)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(5),
            ])
            ->create();

        $standby1 = Trooper::factory()->create();
        $standby1Cet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($standby1)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(4),
            ]);

        $standby2 = Trooper::factory()->create();
        $standby2Cet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($standby2)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(3),
            ]);

        $cancelled = Trooper::factory()->create();
        $cancelledCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($cancelled)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::CANCELLED,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinutes(2),
            ]);

        $recordCountBefore = EventTrooper::where(EventTrooper::EVENT_SHIFT_ID, $this->shift->id)->count();
        $standby1SignedUpAt = $standby1Cet->signed_up_at->copy();
        $standby2SignedUpAt = $standby2Cet->signed_up_at->copy();

        //  station limits are schema-required (NOT NULL); a large limit is the closest
        //  representable equivalent of "unlimited" for the station side
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => null]);
        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 100]);

        (new ReconcileEventRosterJob($this->event, Trooper::factory()->create()))->handle();

        $standby1Cet->refresh();
        $standby2Cet->refresh();
        $cancelledCet->refresh();

        $this->assertSame(EventTrooperStatus::GOING, $standby1Cet->status, 'First standby promoted');
        $this->assertSame(EventTrooperStatus::GOING, $standby2Cet->status, 'Second standby promoted');
        $this->assertSame(EventTrooperStatus::CANCELLED, $cancelledCet->status, 'Cancelled trooper stays cancelled');

        $recordCountAfter = EventTrooper::where(EventTrooper::EVENT_SHIFT_ID, $this->shift->id)->count();
        $this->assertSame($recordCountBefore, $recordCountAfter, 'No duplicate records created');

        $this->assertTrue(
            $standby1Cet->signed_up_at->equalTo($standby1SignedUpAt),
            'First standby signup time unchanged'
        );
        $this->assertTrue(
            $standby2Cet->signed_up_at->equalTo($standby2SignedUpAt),
            'Second standby signup time unchanged'
        );
    }

    public function test_reconcile_org_unlimited_station_full_standby_remains(): void
    {
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => null]);
        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 1]);

        $going = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($going)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinute(),
            ])
            ->create();

        $standby = Trooper::factory()->create();
        $standbyCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($standby)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now(),
            ]);

        (new ReconcileEventRosterJob($this->event, Trooper::factory()->create()))->handle();

        $standbyCet->refresh();
        $this->assertSame(
            EventTrooperStatus::STAND_BY,
            $standbyCet->status,
            'Unlimited org does not bypass the full station limit'
        );
    }

    public function test_reconcile_station_unlimited_org_full_standby_remains(): void
    {
        //  station limits are schema-required (NOT NULL); a large limit is the closest
        //  representable equivalent of "unlimited" for the station side
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 1]);
        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 100]);

        $going = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($going)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now()->subMinute(),
            ])
            ->create();

        $standby = Trooper::factory()->create();
        $standbyCet = EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($standby)
            ->forEventShiftStation($this->targetStation)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => now(),
            ]);

        (new ReconcileEventRosterJob($this->event, Trooper::factory()->create()))->handle();

        $standbyCet->refresh();
        $this->assertSame(
            EventTrooperStatus::STAND_BY,
            $standbyCet->status,
            'Unlimited station does not bypass the full organization limit'
        );
    }

    // ========== EVENT + ORGANIZATION + STATION LIMITS TOGETHER ==========

    private function signUpStationedTrooper(Trooper $trooper): void
    {
        app(SignUpEventTrooperCommandHandler::class)(new SignUpEventTrooperCommand(
            event_shift: $this->shift,
            trooper: $trooper,
            added_by_trooper: $trooper,
            organization_id: $this->org->id,
            event_shift_station_id: $this->targetStation->id,
        ));
    }

    public function test_signup_all_three_limits_have_room_trooper_goes_going(): void
    {
        $this->event->update([Event::TROOPERS_ALLOWED => 3]);
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 2]);
        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 2]);

        $trooper = Trooper::factory()->create();
        $this->signUpStationedTrooper($trooper);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
        ]);
    }

    public function test_signup_event_full_org_and_station_have_room_trooper_goes_standby(): void
    {
        $this->event->update([Event::TROOPERS_ALLOWED => 1]);
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 2]);
        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 2]);

        $existing = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($existing)
            ->forEventShiftStation($this->otherStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => null,
                EventTrooper::IS_HANDLER => false,
            ])
            ->create();

        $trooper = Trooper::factory()->create();
        $this->signUpStationedTrooper($trooper);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::STAND_BY->value,
        ]);
    }

    public function test_signup_org_full_event_and_station_have_room_trooper_goes_standby(): void
    {
        $this->event->update([Event::TROOPERS_ALLOWED => 5]);
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 1]);
        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 2]);

        $existing = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($existing)
            ->forEventShiftStation($this->otherStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
            ])
            ->create();

        $trooper = Trooper::factory()->create();
        $this->signUpStationedTrooper($trooper);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::STAND_BY->value,
        ]);
    }

    public function test_signup_station_full_event_and_org_have_room_trooper_goes_standby(): void
    {
        $this->event->update([Event::TROOPERS_ALLOWED => 5]);
        $this->eventOrg->update([EventOrganization::TROOPERS_ALLOWED => 3]);
        $this->targetStation->update([EventShiftStation::TROOPERS_ALLOWED => 1]);

        $existing = Trooper::factory()->create();
        EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($existing)
            ->forEventShiftStation($this->targetStation)
            ->asGoing()
            ->state([
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
            ])
            ->create();

        $trooper = Trooper::factory()->create();
        $this->signUpStationedTrooper($trooper);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::STAND_BY->value,
        ]);
    }

}
