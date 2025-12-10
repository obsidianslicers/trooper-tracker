<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Models\Trooper
 */
class TrooperTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_attributes_correctly(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            'membership_status' => MembershipStatus::ACTIVE,
            'membership_role' => MembershipRole::MEMBER,
            'email' => 'Test.Email@Example.COM',
        ]);

        // Act
        $refreshed_trooper = $trooper->fresh();

        // Assert
        $this->assertInstanceOf(MembershipStatus::class, $refreshed_trooper->membership_status);
        $this->assertInstanceOf(MembershipRole::class, $refreshed_trooper->membership_role);
        $this->assertSame('test.email@example.com', $refreshed_trooper->email);
    }

    public function test_is_admin_returns_correct_value(): void
    {
        // Arrange
        $admin_trooper = Trooper::factory()->make(['membership_role' => MembershipRole::ADMINISTRATOR]);
        $member_trooper = Trooper::factory()->make(['membership_role' => MembershipRole::MEMBER]);

        // Act & Assert
        $this->assertTrue($admin_trooper->isAdministrator());
        $this->assertFalse($member_trooper->isAdministrator());
    }

    public function test_is_active_returns_correct_value(): void
    {
        // Arrange
        $active_trooper = Trooper::factory()->make(['membership_status' => MembershipStatus::ACTIVE]);
        $pending_trooper = Trooper::factory()->make(['membership_status' => MembershipStatus::PENDING]);

        // Act & Assert
        $this->assertTrue($active_trooper->isActive());
        $this->assertFalse($pending_trooper->isActive());
    }

    public function test_is_denied_returns_correct_value(): void
    {
        // Arrange
        $denied_trooper = Trooper::factory()->make(['membership_status' => MembershipStatus::DENIED]);
        $active_trooper = Trooper::factory()->make(['membership_status' => MembershipStatus::ACTIVE]);

        // Act & Assert
        $this->assertTrue($denied_trooper->isDenied());
        $this->assertFalse($active_trooper->isDenied());
    }

    public function test_attach_and_detach_costume_work_correctly(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $costume = OrganizationCostume::factory()->create();

        // Act (Attach)
        $trooper->attachCostume($costume->id);

        // Assert (Attach)
        $this->assertDatabaseHas(TrooperCostume::class, [
            'trooper_id' => $trooper->id,
            'costume_id' => $costume->id,
        ]);

        // Act (Detach)
        $trooper->detachCostume($costume->id);

        // Assert (Detach)
        $this->assertSoftDeleted(TrooperCostume::class, [
            'trooper_id' => $trooper->id,
            'costume_id' => $costume->id,
        ]);
    }

    public function test_has_active_organization_status_returns_correct_value(): void
    {
        // Arrange
        $trooper_with_active = Trooper::factory()->create();
        $trooper_with_inactive = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $trooper_with_active->trooper_assignments()->create([
            'organization_id' => $organization->id,
            'is_member' => true,
        ]);

        $trooper_with_inactive->trooper_assignments()->create([
            'organization_id' => $organization->id,
            'is_member' => false,
        ]);

        // Act & Assert
        $this->assertTrue($trooper_with_active->hasActiveOrganizationStatus());
        $this->assertFalse($trooper_with_inactive->hasActiveOrganizationStatus());
    }
}
