<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Troopers;

use App\Enums\MembershipRole;
use App\Models\Trooper;
use App\Services\Troopers\GetTrooperAdministratorsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetTrooperAdministratorsQuery.
 *
 * Verifies:
 * - Retrieves all troopers with administrator role
 * - Returns empty collection when no administrators exist
 * - Excludes non-administrator troopers
 * - Returns Collection instance
 */
class GetTrooperAdministratorsQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_collection(): void
    {
        // Arrange
        $subject = new GetTrooperAdministratorsQuery();

        // Act
        $result = $subject();

        // Assert
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    public function test_invoke_returns_all_administrator_troopers(): void
    {
        // Arrange
        $subject = new GetTrooperAdministratorsQuery();
        $admin1 = Trooper::factory()->asAdministrator()->create();
        $admin2 = Trooper::factory()->asAdministrator()->create();
        $admin3 = Trooper::factory()->asAdministrator()->create();

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(3, $result);
        $this->assertTrue($result->contains('id', $admin1->id));
        $this->assertTrue($result->contains('id', $admin2->id));
        $this->assertTrue($result->contains('id', $admin3->id));
    }

    public function test_invoke_excludes_non_administrator_troopers(): void
    {
        // Arrange
        $subject = new GetTrooperAdministratorsQuery();
        $admin = Trooper::factory()->asAdministrator()->create();
        $moderator = Trooper::factory()->asModerator()->create();
        $regular = Trooper::factory()->asActive()->create();

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains('id', $admin->id));
        $this->assertFalse($result->contains('id', $moderator->id));
        $this->assertFalse($result->contains('id', $regular->id));
    }

    public function test_invoke_returns_empty_collection_when_no_administrators(): void
    {
        // Arrange
        $subject = new GetTrooperAdministratorsQuery();
        Trooper::factory()->asActive()->create();
        Trooper::factory()->asModerator()->create();

        // Act
        $result = $subject();

        // Assert
        $this->assertTrue($result->isEmpty());
    }

    public function test_invoke_filters_by_membership_role_administrator(): void
    {
        // Arrange
        $subject = new GetTrooperAdministratorsQuery();
        Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);
        Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MODERATOR,
        ]);
        Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER,
        ]);

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(MembershipRole::ADMINISTRATOR, $result->first()->membership_role);
    }

    public function test_invoke_returns_all_matching_records_without_pagination(): void
    {
        // Arrange
        $subject = new GetTrooperAdministratorsQuery();

        // Create many administrators
        for ($i = 0; $i < 25; $i++)
        {
            Trooper::factory()->asAdministrator()->create();
        }

        // Act
        $result = $subject();

        // Assert - should return all 25 without pagination
        $this->assertCount(25, $result);
    }

    public function test_invoke_includes_pending_administrators(): void
    {
        // Arrange
        $subject = new GetTrooperAdministratorsQuery();
        $pending_admin = Trooper::factory()->asPending()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains('id', $pending_admin->id));
    }

    public function test_invoke_includes_retired_administrators(): void
    {
        // Arrange
        $subject = new GetTrooperAdministratorsQuery();
        $retired_admin = Trooper::factory()->asRetired()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
        ]);

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains('id', $retired_admin->id));
    }

    public function test_invoke_returns_trooper_instances(): void
    {
        // Arrange
        $subject = new GetTrooperAdministratorsQuery();
        Trooper::factory()->asAdministrator()->create();

        // Act
        $result = $subject();

        // Assert
        $this->assertInstanceOf(Trooper::class, $result->first());
    }

    public function test_invoke_preserves_trooper_attributes(): void
    {
        // Arrange
        $subject = new GetTrooperAdministratorsQuery();
        $admin = Trooper::factory()->asAdministrator()->create([
            Trooper::NAME => 'Admin Trooper',
            Trooper::EMAIL => 'admin@501st.com',
        ]);

        // Act
        $result = $subject();

        // Assert
        $trooper = $result->first();
        $this->assertEquals('Admin Trooper', $trooper->name);
        $this->assertEquals('admin@501st.com', $trooper->email);
        $this->assertEquals(MembershipRole::ADMINISTRATOR, $trooper->membership_role);
    }

    public function test_invoke_can_be_called_multiple_times(): void
    {
        // Arrange
        $subject = new GetTrooperAdministratorsQuery();
        Trooper::factory()->asAdministrator()->count(3)->create();

        // Act
        $result1 = $subject();
        $result2 = $subject();

        // Assert - both calls should return same data
        $this->assertCount(3, $result1);
        $this->assertCount(3, $result2);
        $this->assertEquals($result1->pluck('id'), $result2->pluck('id'));
    }
}
