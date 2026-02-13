<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Reports\Queries;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipRole;
use App\Features\Reports\Queries\GetTrooperEventSummaryQuery;
use App\Features\Reports\Queries\GetTrooperEventSummaryQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetTrooperEventSummaryQueryHandler.
 *
 * Verifies:
 * - Returns troopers who attended events within lookback
 * - Calculates event_shifts_count (total shifts attended)
 * - Calculates events_count (unique events attended)
 * - Provides attended_event_ids list
 * - Orders by trooper name
 */
class GetTrooperEventSummaryQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_troopers_with_attended_events(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => now()->subDays(10),
        ]);
        $shift = EventShift::factory()->create([EventShift::EVENT_ID => $event->id]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
        ]);

        $query = new GetTrooperEventSummaryQuery($moderator, 30);
        $subject = new GetTrooperEventSummaryQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($trooper->id, $result->first()->id);
    }

    public function test_invoke_excludes_troopers_without_attendance(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        Trooper::factory()->create();

        $query = new GetTrooperEventSummaryQuery($moderator, 30);
        $subject = new GetTrooperEventSummaryQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_excludes_events_outside_lookback(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => now()->subDays(40),
        ]);
        $shift = EventShift::factory()->create([EventShift::EVENT_ID => $event->id]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
        ]);

        $query = new GetTrooperEventSummaryQuery($moderator, 30);
        $subject = new GetTrooperEventSummaryQueryHandler();

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

        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create([
            Event::STATUS => EventStatus::OPEN,
            Event::EVENT_START => now()->subDays(10),
        ]);
        $shift = EventShift::factory()->create([EventShift::EVENT_ID => $event->id]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
        ]);

        $query = new GetTrooperEventSummaryQuery($moderator, 30);
        $subject = new GetTrooperEventSummaryQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_calculates_event_shifts_count(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => now()->subDays(10),
        ]);

        $shift1 = EventShift::factory()->create([EventShift::EVENT_ID => $event->id]);
        $shift2 = EventShift::factory()->create([EventShift::EVENT_ID => $event->id]);
        $shift3 = EventShift::factory()->create([EventShift::EVENT_ID => $event->id]);

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

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift3->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
        ]);

        $query = new GetTrooperEventSummaryQuery($moderator, 30);
        $subject = new GetTrooperEventSummaryQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(3, $result->first()->event_shifts_count);
    }

    public function test_invoke_calculates_unique_events_count(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        $trooper = Trooper::factory()->create();

        $event1 = Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => now()->subDays(10),
        ]);

        $event2 = Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => now()->subDays(5),
        ]);

        $shift1a = EventShift::factory()->create([EventShift::EVENT_ID => $event1->id]);
        $shift1b = EventShift::factory()->create([EventShift::EVENT_ID => $event1->id]);
        $shift2 = EventShift::factory()->create([EventShift::EVENT_ID => $event2->id]);

        // Same trooper: 3 shifts across 2 events
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift1a->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
        ]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift1b->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
        ]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift2->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
        ]);

        $query = new GetTrooperEventSummaryQuery($moderator, 30);
        $subject = new GetTrooperEventSummaryQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(3, $result->first()->event_shifts_count);
        $this->assertEquals(2, $result->first()->events_count);
    }

    public function test_invoke_provides_attended_event_ids(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        $trooper = Trooper::factory()->create();
        $event1 = Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => now()->subDays(10),
        ]);
        $event2 = Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => now()->subDays(5),
        ]);

        $shift1 = EventShift::factory()->create([EventShift::EVENT_ID => $event1->id]);
        $shift2 = EventShift::factory()->create([EventShift::EVENT_ID => $event2->id]);

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

        $query = new GetTrooperEventSummaryQuery($moderator, 30);
        $subject = new GetTrooperEventSummaryQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->attended_event_ids->contains($event1->id));
        $this->assertTrue($result->first()->attended_event_ids->contains($event2->id));
    }

    public function test_invoke_orders_by_trooper_name(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        $trooper1 = Trooper::factory()->create([Trooper::DISPLAY_NAME => 'Zulu Trooper']);
        $trooper2 = Trooper::factory()->create([Trooper::DISPLAY_NAME => 'Alpha Trooper']);

        $event = Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => now()->subDays(10),
        ]);

        $shift = EventShift::factory()->create([EventShift::EVENT_ID => $event->id]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper1->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
        ]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper2->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
        ]);

        $query = new GetTrooperEventSummaryQuery($moderator, 30);
        $subject = new GetTrooperEventSummaryQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals($trooper2->id, $result->first()->id);
        $this->assertEquals($trooper1->id, $result->last()->id);
    }
}
