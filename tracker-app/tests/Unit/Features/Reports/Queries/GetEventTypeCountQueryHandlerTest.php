<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Reports\Queries;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\EventType;
use App\Enums\MembershipRole;
use App\Features\Reports\Queries\GetEventTypeCountQuery;
use App\Features\Reports\Queries\GetEventTypeCountQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetEventTypeCountQueryHandler.
 *
 * Verifies:
 * - Groups events by type
 * - Calculates counts per type
 * - Computes total and unique trooper counts per type
 * - Filters by lookback date and closed status
 */
class GetEventTypeCountQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_groups_events_by_type(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        Event::factory()->count(2)->create([
            Event::TYPE => EventType::REGULAR,
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => now()->subDays(10),
            Event::CREATED_ID => $moderator->id,
        ]);

        Event::factory()->count(3)->create([
            Event::TYPE => EventType::CHARITY,
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => now()->subDays(10),
            Event::CREATED_ID => $moderator->id,
        ]);

        $query = new GetEventTypeCountQuery($moderator, 30);
        $subject = new GetEventTypeCountQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(2, $result);

        $regular = $result->firstWhere('event_type', EventType::REGULAR);
        $this->assertEquals(2, $regular->count);

        $charity = $result->firstWhere('event_type', EventType::CHARITY);
        $this->assertEquals(3, $charity->count);
    }

    public function test_invoke_calculates_total_trooper_count_per_type(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        $event = Event::factory()->create([
            Event::TYPE => EventType::REGULAR,
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => now()->subDays(10),
            Event::CREATED_ID => $moderator->id,
        ]);

        $shift = EventShift::factory()->create([EventShift::EVENT_ID => $event->id]);

        EventTrooper::factory()->count(5)->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
        ]);

        $query = new GetEventTypeCountQuery($moderator, 30);
        $subject = new GetEventTypeCountQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(5, $result->first()->total_trooper_count);
    }

    public function test_invoke_calculates_unique_trooper_count_per_type(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        $event1 = Event::factory()->create([
            Event::TYPE => EventType::REGULAR,
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => now()->subDays(10),
            Event::CREATED_ID => $moderator->id,
        ]);

        $event2 = Event::factory()->create([
            Event::TYPE => EventType::REGULAR,
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => now()->subDays(5),
            Event::CREATED_ID => $moderator->id,
        ]);

        $shift1 = EventShift::factory()->create([EventShift::EVENT_ID => $event1->id]);
        $shift2 = EventShift::factory()->create([EventShift::EVENT_ID => $event2->id]);

        $trooper = Trooper::factory()->create();

        // Same trooper in two events = 2 total but 1 unique
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift1->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
        ]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift2->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
        ]);

        $query = new GetEventTypeCountQuery($moderator, 30);
        $subject = new GetEventTypeCountQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(2, $result->first()->total_trooper_count);
        $this->assertEquals(1, $result->first()->unique_trooper_count);
    }

    public function test_invoke_excludes_events_outside_lookback(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        Event::factory()->create([
            Event::TYPE => EventType::REGULAR,
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => now()->subDays(40),
            Event::CREATED_ID => $moderator->id,
        ]);

        $query = new GetEventTypeCountQuery($moderator, 30);
        $subject = new GetEventTypeCountQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_excludes_non_closed_events(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        Event::factory()->create([
            Event::TYPE => EventType::REGULAR,
            Event::STATUS => EventStatus::OPEN,
            Event::EVENT_START => now()->subDays(10),
            Event::CREATED_ID => $moderator->id,
        ]);

        $query = new GetEventTypeCountQuery($moderator, 30);
        $subject = new GetEventTypeCountQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_returns_empty_collection_when_no_events(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        $query = new GetEventTypeCountQuery($moderator, 30);
        $subject = new GetEventTypeCountQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }
}
