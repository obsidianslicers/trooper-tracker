<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Reports\Queries;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipRole;
use App\Features\Reports\Queries\GetEventSummaryQuery;
use App\Features\Reports\Queries\GetEventSummaryQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetEventSummaryQueryHandler.
 *
 * Verifies:
 * - Returns closed events for moderator
 * - Filters by lookback date
 * - Calculates shift counts and trooper metrics
 * - Orders by event_end descending
 */
class GetEventSummaryQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_closed_events_within_lookback(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        $event = Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => now()->subDays(10),
            Event::CREATED_ID => $moderator->id,
        ]);

        $query = new GetEventSummaryQuery($moderator, 30);
        $subject = new GetEventSummaryQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($event->id, $result->first()->id);
    }

    public function test_invoke_excludes_events_outside_lookback(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => now()->subDays(40),
            Event::CREATED_ID => $moderator->id,
        ]);

        $query = new GetEventSummaryQuery($moderator, 30);
        $subject = new GetEventSummaryQueryHandler();

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
            Event::STATUS => EventStatus::OPEN,
            Event::EVENT_START => now()->subDays(10),
            Event::CREATED_ID => $moderator->id,
        ]);

        $query = new GetEventSummaryQuery($moderator, 30);
        $subject = new GetEventSummaryQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_calculates_shift_count(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        $event = Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => now()->subDays(10),
            Event::CREATED_ID => $moderator->id,
        ]);

        EventShift::factory()->count(3)->create([
            EventShift::EVENT_ID => $event->id,
        ]);

        $query = new GetEventSummaryQuery($moderator, 30);
        $subject = new GetEventSummaryQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(3, $result->first()->event_shifts_count);
    }

    public function test_invoke_calculates_total_trooper_count(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        $event = Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => now()->subDays(10),
            Event::CREATED_ID => $moderator->id,
        ]);

        $shift = EventShift::factory()->create([
            EventShift::EVENT_ID => $event->id,
        ]);

        EventTrooper::factory()->count(5)->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
        ]);

        $query = new GetEventSummaryQuery($moderator, 30);
        $subject = new GetEventSummaryQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(5, $result->first()->total_trooper_count);
    }

    public function test_invoke_calculates_unique_trooper_count(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        $event = Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => now()->subDays(10),
            Event::CREATED_ID => $moderator->id,
        ]);

        $shift1 = EventShift::factory()->create([EventShift::EVENT_ID => $event->id]);
        $shift2 = EventShift::factory()->create([EventShift::EVENT_ID => $event->id]);

        $trooper = Trooper::factory()->create();

        // Same trooper in two shifts = 2 total but 1 unique
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

        $query = new GetEventSummaryQuery($moderator, 30);
        $subject = new GetEventSummaryQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(2, $result->first()->total_trooper_count);
        $this->assertEquals(1, $result->first()->unique_trooper_count);
    }

    public function test_invoke_orders_by_event_end_descending(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        $event1 = Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => now()->subDays(20),
            Event::EVENT_END => now()->subDays(19),
            Event::CREATED_ID => $moderator->id,
        ]);

        $event2 = Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => now()->subDays(10),
            Event::EVENT_END => now()->subDays(9),
            Event::CREATED_ID => $moderator->id,
        ]);

        $query = new GetEventSummaryQuery($moderator, 30);
        $subject = new GetEventSummaryQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals($event2->id, $result->first()->id);
        $this->assertEquals($event1->id, $result->last()->id);
    }
}
