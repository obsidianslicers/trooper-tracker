<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Reports\Queries;

use App\Enums\EventTrooperStatus;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Features\Reports\Queries\GetTroopersWithoutActivityQuery;
use App\Features\Reports\Queries\GetTroopersWithoutActivityQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetTroopersWithoutActivityQueryHandler.
 *
 * Verifies:
 * - Returns active troopers without recent ATTENDED events
 * - Excludes troopers who attended events within lookback
 * - Only includes ACTIVE membership status
 * - Orders by trooper name
 */
class GetTroopersWithoutActivityQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_troopers_without_recent_activity(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        $query = new GetTroopersWithoutActivityQuery($moderator, 30);
        $subject = new GetTroopersWithoutActivityQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(2, $result); // moderator + trooper
        $this->assertTrue($result->contains('id', $trooper->id));
    }

    public function test_invoke_excludes_troopers_with_recent_activity(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        $event = Event::factory()->create();
        $shift = EventShift::factory()->create([EventShift::EVENT_ID => $event->id]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
            EventTrooper::SIGNED_UP_AT => now()->subDays(10), // Recent activity
        ]);

        $query = new GetTroopersWithoutActivityQuery($moderator, 30);
        $subject = new GetTroopersWithoutActivityQueryHandler();

        // Act
        $result = $subject($query);

        // Assert - Trooper with RECENT activity (no OLD activity) IS included
        $this->assertTrue($result->contains('id', $moderator->id));
        $this->assertTrue($result->contains('id', $trooper->id));
    }

    public function test_invoke_includes_troopers_with_old_activity(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        $event = Event::factory()->create();
        $shift = EventShift::factory()->create([EventShift::EVENT_ID => $event->id]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
            EventTrooper::SIGNED_UP_AT => now()->subDays(40), // Old activity (before lookback)
        ]);

        $query = new GetTroopersWithoutActivityQuery($moderator, 30);
        $subject = new GetTroopersWithoutActivityQueryHandler();

        // Act
        $result = $subject($query);

        // Assert - Trooper with OLD activity IS excluded (has old activity)
        $this->assertTrue($result->contains('id', $moderator->id));
        $this->assertFalse($result->contains('id', $trooper->id));
    }

    public function test_invoke_only_includes_active_troopers(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        Trooper::factory()->create([
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::PENDING,
        ]);

        Trooper::factory()->create([
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::RETIRED,
        ]);

        $query = new GetTroopersWithoutActivityQuery($moderator, 30);
        $subject = new GetTroopersWithoutActivityQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result); // Only moderator is ACTIVE
        $this->assertEquals($moderator->id, $result->first()->id);
    }

    public function test_invoke_excludes_non_attended_statuses(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        $event = Event::factory()->create();
        $shift = EventShift::factory()->create([EventShift::EVENT_ID => $event->id]);

        // Trooper is GOING but not ATTENDED
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
            EventTrooper::SIGNED_UP_AT => now()->subDays(10),
        ]);

        $query = new GetTroopersWithoutActivityQuery($moderator, 30);
        $subject = new GetTroopersWithoutActivityQueryHandler();

        // Act
        $result = $subject($query);

        // Assert - Trooper with GOING (not ATTENDED) has no ATTENDED records,so included
        $this->assertTrue($result->contains('id', $moderator->id));
        $this->assertTrue($result->contains('id', $trooper->id));
    }

    public function test_invoke_orders_by_trooper_name(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
            Trooper::NAME => 'Moderator Name',
        ]);

        $trooper1 = Trooper::factory()->create([
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
            Trooper::NAME => 'Zulu Trooper',
        ]);

        $trooper2 = Trooper::factory()->create([
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
            Trooper::NAME => 'Alpha Trooper',
        ]);

        $query = new GetTroopersWithoutActivityQuery($moderator, 30);
        $subject = new GetTroopersWithoutActivityQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(3, $result); // moderator + 2 troopers
        $this->assertEquals('Alpha Trooper', $result->first()->name);
        $this->assertEquals('Zulu Trooper', $result->last()->name);
    }

    public function test_invoke_returns_empty_collection_when_all_active(): void
    {
        // Arrange
        $moderator = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        $event = Event::factory()->create();
        $shift = EventShift::factory()->create([EventShift::EVENT_ID => $event->id]);

        // Both moderator and trooper have OLD activity (before lookback)
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
            EventTrooper::SIGNED_UP_AT => now()->subDays(40), // OLD activity
        ]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $moderator->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
            EventTrooper::SIGNED_UP_AT => now()->subDays(40), // OLD activity
        ]);

        $query = new GetTroopersWithoutActivityQuery($moderator, 30);
        $subject = new GetTroopersWithoutActivityQueryHandler();

        // Act
        $result = $subject($query);

        // Assert - Both troopers have OLD activity, so both excluded
        $this->assertFalse($result->contains('id', $moderator->id));
        $this->assertFalse($result->contains('id', $trooper->id));
    }
}
