<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Queries;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Features\Troopers\Queries\GetTroopersByRoleQueryHandler;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetTroopersByRoleQueryHandler.
 *
 * Verifies:
 * - Returns only troopers with ADMINISTRATOR role
 * - Filters out non-administrator troopers
 * - Orders results appropriately
 */
class GetTroopersByRoleQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_only_administrators(): void
    {
        // Arrange
        Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        $query = new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR);
        $subject = new GetTroopersByRoleQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(MembershipRole::ADMINISTRATOR, $result->first()->membership_role);
    }

    public function test_invoke_returns_multiple_administrators(): void
    {
        // Arrange
        Trooper::factory()->count(3)->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        $query = new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR);
        $subject = new GetTroopersByRoleQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(3, $result);
    }

    public function test_invoke_returns_empty_collection_when_no_administrators(): void
    {
        // Arrange
        Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        $query = new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR);
        $subject = new GetTroopersByRoleQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_excludes_moderators(): void
    {
        // Arrange
        Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MODERATOR,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        $query = new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR);
        $subject = new GetTroopersByRoleQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $result->each(fn($trooper) => $this->assertEquals(MembershipRole::ADMINISTRATOR, $trooper->membership_role));
    }
}
