<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Troopers;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Services\Troopers\AssignTrooperMembershipsCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for AssignTrooperMembershipsCommand.
 *
 * Verifies:
 * - Creates new assignments with membership status.
 * - Updates existing assignments' membership status.
 * - Handles is_member flag correctly (true/false/missing).
 * - Processes multiple organizations in one invocation.
 * - Defaults to false when is_member is not provided.
 */
class AssignTrooperMembershipsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_new_assignment_with_membership_enabled(): void
    {
        // Arrange
        $subject = new AssignTrooperMembershipsCommand();

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $organizations_data = [
            $organization->id => [
                'is_member' => true,
            ],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $assignment = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $organization->id)
            ->first();

        $this->assertNotNull($assignment);
        $this->assertTrue($assignment->is_member);
    }

    public function test_invoke_creates_new_assignment_with_membership_disabled(): void
    {
        // Arrange
        $subject = new AssignTrooperMembershipsCommand();

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $organizations_data = [
            $organization->id => [
                'is_member' => false,
            ],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $assignment = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $organization->id)
            ->first();

        $this->assertNotNull($assignment);
        $this->assertFalse($assignment->is_member);
    }

    public function test_invoke_updates_existing_assignment_membership_status(): void
    {
        // Arrange
        $subject = new AssignTrooperMembershipsCommand();

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $existing_assignment = TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => false,
        ]);

        $organizations_data = [
            $organization->id => [
                'is_member' => true,
            ],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $existing_assignment->refresh();
        $this->assertTrue($existing_assignment->is_member);
    }

    public function test_invoke_defaults_to_false_when_is_member_not_provided(): void
    {
        // Arrange
        $subject = new AssignTrooperMembershipsCommand();

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $organizations_data = [
            $organization->id => [],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $assignment = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $organization->id)
            ->first();

        $this->assertNotNull($assignment);
        $this->assertFalse($assignment->is_member);
    }

    public function test_invoke_processes_multiple_organizations(): void
    {
        // Arrange
        $subject = new AssignTrooperMembershipsCommand();

        $trooper = Trooper::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org3 = Organization::factory()->create();

        $organizations_data = [
            $org1->id => ['is_member' => true],
            $org2->id => ['is_member' => false],
            $org3->id => ['is_member' => true],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $this->assertEquals(3, $trooper->trooper_assignments()->count());

        $assignment1 = $trooper->trooper_assignments()
            ->where(TrooperAssignment::ORGANIZATION_ID, $org1->id)
            ->first();
        $this->assertTrue($assignment1->is_member);

        $assignment2 = $trooper->trooper_assignments()
            ->where(TrooperAssignment::ORGANIZATION_ID, $org2->id)
            ->first();
        $this->assertFalse($assignment2->is_member);

        $assignment3 = $trooper->trooper_assignments()
            ->where(TrooperAssignment::ORGANIZATION_ID, $org3->id)
            ->first();
        $this->assertTrue($assignment3->is_member);
    }

    public function test_invoke_updates_is_member_from_true_to_false(): void
    {
        // Arrange
        $subject = new AssignTrooperMembershipsCommand();

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $existing_assignment = TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        $organizations_data = [
            $organization->id => [
                'is_member' => false,
            ],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $existing_assignment->refresh();
        $this->assertFalse($existing_assignment->is_member);
    }

    public function test_invoke_preserves_other_assignment_attributes(): void
    {
        // Arrange
        $subject = new AssignTrooperMembershipsCommand();

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $existing_assignment = TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => false,
            TrooperAssignment::CAN_NOTIFY => true,
        ]);

        $organizations_data = [
            $organization->id => [
                'is_member' => true,
            ],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $existing_assignment->refresh();
        $this->assertTrue($existing_assignment->is_member);
        $this->assertTrue($existing_assignment->can_notify);
        $this->assertEquals($trooper->id, $existing_assignment->trooper_id);
        $this->assertEquals($organization->id, $existing_assignment->organization_id);
    }

    public function test_invoke_handles_mixed_new_and_existing_assignments(): void
    {
        // Arrange
        $subject = new AssignTrooperMembershipsCommand();

        $trooper = Trooper::factory()->create();
        $existing_org = Organization::factory()->create();
        $new_org = Organization::factory()->create();

        // Create existing assignment
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $existing_org->id,
            TrooperAssignment::IS_MEMBER => false,
        ]);

        $organizations_data = [
            $existing_org->id => ['is_member' => true],
            $new_org->id => ['is_member' => false],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $this->assertEquals(2, $trooper->trooper_assignments()->count());

        $existing_assignment = $trooper->trooper_assignments()
            ->where(TrooperAssignment::ORGANIZATION_ID, $existing_org->id)
            ->first();
        $this->assertTrue($existing_assignment->is_member);

        $new_assignment = $trooper->trooper_assignments()
            ->where(TrooperAssignment::ORGANIZATION_ID, $new_org->id)
            ->first();
        $this->assertFalse($new_assignment->is_member);
    }
}
