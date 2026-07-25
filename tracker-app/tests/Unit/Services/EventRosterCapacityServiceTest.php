<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Services\EventRosterCapacityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit coverage for the single source of truth on roster capacity.
 *
 * @see EventRosterCapacityService
 */
class EventRosterCapacityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_limit_has_room_treats_null_as_unlimited(): void
    {
        $subject = new EventRosterCapacityService;

        $this->assertTrue($subject->limitHasRoom(null, 999));
    }

    public function test_limit_has_room_compares_count_to_limit(): void
    {
        $subject = new EventRosterCapacityService;

        $this->assertTrue($subject->limitHasRoom(2, 1));
        $this->assertFalse($subject->limitHasRoom(2, 2));
        $this->assertFalse($subject->limitHasRoom(2, 3));
    }

    public function test_station_has_room_requires_a_limit(): void
    {
        $subject = new EventRosterCapacityService;

        $this->assertFalse($subject->stationHasRoom(null, 0));
        $this->assertTrue($subject->stationHasRoom(2, 1));
        $this->assertFalse($subject->stationHasRoom(2, 2));
    }

    public function test_can_go_at_station_allows_non_stationed_shifts(): void
    {
        $shift = $this->makeShift();

        $subject = new EventRosterCapacityService;

        $this->assertTrue($subject->canGoAtStation($shift, null));
    }

    public function test_can_go_at_station_requires_a_station_on_stationed_shifts(): void
    {
        $shift = $this->makeShift();
        $this->makeStation($shift, 1);

        $subject = new EventRosterCapacityService;

        $this->assertFalse($subject->canGoAtStation($shift, null));
    }

    public function test_can_go_at_station_checks_station_capacity(): void
    {
        $shift = $this->makeShift();
        $station = $this->makeStation($shift, 1);
        $occupant = $this->addGoingTrooper($shift, $station);

        $subject = new EventRosterCapacityService;

        $this->assertFalse($subject->canGoAtStation($shift, $station->id));
        $this->assertTrue($subject->canGoAtStation($shift, $station->id, $occupant->id));
    }

    public function test_can_go_is_true_when_every_limit_has_room(): void
    {
        $shift = $this->makeShift();
        $station = $this->makeStation($shift, 2);
        $this->addGoingTrooper($shift, $station);

        $subject = new EventRosterCapacityService;

        $this->assertTrue($subject->canGo($shift, null, $station->id, false));
    }

    public function test_can_go_is_false_when_station_is_full(): void
    {
        $shift = $this->makeShift();
        $station = $this->makeStation($shift, 1);
        $this->addGoingTrooper($shift, $station);

        $subject = new EventRosterCapacityService;

        $this->assertFalse($subject->canGo($shift, null, $station->id, false));
    }

    public function test_can_go_is_false_when_event_limit_is_full(): void
    {
        $shift = $this->makeShift(troopers_allowed: 1);
        $this->addGoingTrooper($shift);

        $subject = new EventRosterCapacityService;

        $this->assertFalse($subject->canGo($shift, null, null, false));
    }

    public function test_can_go_is_false_when_org_limit_is_full(): void
    {
        $shift = $this->makeShift();
        $organization = Organization::factory()->create();

        EventOrganization::factory()
            ->forEvent($shift->event)
            ->forOrganization($organization)
            ->canAttend()
            ->create([EventOrganization::TROOPERS_ALLOWED => 1]);

        $this->addGoingTrooper($shift, organization_id: $organization->id);

        $subject = new EventRosterCapacityService;

        $this->assertFalse($subject->canGo($shift, $organization->id, null, false));
    }

    private function makeShift(?int $troopers_allowed = null): EventShift
    {
        $event = Event::factory()->create([
            Event::TROOPERS_ALLOWED => $troopers_allowed,
            Event::HANDLERS_ALLOWED => null,
        ]);

        return EventShift::factory()->forEvent($event)->create();
    }

    private function makeStation(EventShift $shift, int $troopers_allowed): EventShiftStation
    {
        return EventShiftStation::factory()
            ->forEventShift($shift)
            ->state([EventShiftStation::TROOPERS_ALLOWED => $troopers_allowed])
            ->create();
    }

    private function addGoingTrooper(
        EventShift $shift,
        ?EventShiftStation $station = null,
        ?int $organization_id = null,
    ): EventTrooper {
        $factory = EventTrooper::factory()->forEventShift($shift)->asGoing();

        if ($station !== null)
        {
            $factory = $factory->forEventShiftStation($station);
        }

        return $factory->create([
            EventTrooper::IS_HANDLER => false,
            EventTrooper::ORGANIZATION_ID => $organization_id,
        ]);
    }
}
