<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Reports\Queries;

use App\Enums\EventTrooperStatus;
use App\Enums\MembershipRole;
use App\Features\Reports\Queries\GetStatusChangeLogQuery;
use App\Features\Reports\Queries\GetStatusChangeLogQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetStatusChangeLogQueryHandler.
 *
 * Verifies:
 * - Returns ATTENDED status changes within lookback
 * - Excludes self-updates (where updated_id = trooper_id)
 * - Filters by moderator scope
 * - Orders by updated_at descending
 */
class GetStatusChangeLogQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_attended_status_changes(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->create([EventShift::EVENT_ID => $event->id]);

        $event_trooper = EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
            EventTrooper::UPDATED_ID => $moderator->id,
            EventTrooper::UPDATED_AT => now()->subDays(5),
        ]);

        $query = new GetStatusChangeLogQuery($moderator, 30);
        $subject = new GetStatusChangeLogQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($event_trooper->id, $result->first()->id);
    }

    public function test_invoke_excludes_self_updates(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->create([EventShift::EVENT_ID => $event->id]);

        // Self-update: trooper updated their own status
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
            EventTrooper::UPDATED_ID => $trooper->id,
            EventTrooper::UPDATED_AT => now()->subDays(5),
        ]);

        $query = new GetStatusChangeLogQuery($moderator, 30);
        $subject = new GetStatusChangeLogQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_excludes_updates_outside_lookback(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->create([EventShift::EVENT_ID => $event->id]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
            EventTrooper::UPDATED_ID => $moderator->id,
            EventTrooper::UPDATED_AT => now()->subDays(40),
        ]);

        $query = new GetStatusChangeLogQuery($moderator, 30);
        $subject = new GetStatusChangeLogQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_excludes_non_attended_status(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->create([EventShift::EVENT_ID => $event->id]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
            EventTrooper::UPDATED_ID => $moderator->id,
            EventTrooper::UPDATED_AT => now()->subDays(5),
        ]);

        $query = new GetStatusChangeLogQuery($moderator, 30);
        $subject = new GetStatusChangeLogQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_orders_by_updated_at_descending(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create();
        $shift1 = EventShift::factory()->create([EventShift::EVENT_ID => $event->id]);
        $shift2 = EventShift::factory()->create([EventShift::EVENT_ID => $event->id]);

        $et1 = EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift1->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
            EventTrooper::UPDATED_ID => $moderator->id,
            EventTrooper::UPDATED_AT => now()->subDays(10),
        ]);

        $et2 = EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift2->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
            EventTrooper::UPDATED_ID => $moderator->id,
            EventTrooper::UPDATED_AT => now()->subDays(5),
        ]);

        $query = new GetStatusChangeLogQuery($moderator, 30);
        $subject = new GetStatusChangeLogQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals($et2->id, $result->first()->id);
        $this->assertEquals($et1->id, $result->last()->id);
    }

    public function test_invoke_loads_relationships(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->create([EventShift::EVENT_ID => $event->id]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
            EventTrooper::UPDATED_ID => $moderator->id,
            EventTrooper::UPDATED_AT => now()->subDays(5),
        ]);

        $query = new GetStatusChangeLogQuery($moderator, 30);
        $subject = new GetStatusChangeLogQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->relationLoaded('trooper'));
        $this->assertTrue($result->first()->relationLoaded('event_shift'));
        $this->assertTrue($result->first()->event_shift->relationLoaded('event'));
        $this->assertTrue($result->first()->event_shift->relationLoaded('updated_by'));
    }
}
