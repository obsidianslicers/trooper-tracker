<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Troopers;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Services\Troopers\AssignTrooperNotificationsCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for AssignTrooperNotificationsCommand.
 *
 * Verifies:
 * - Creates new assignments with notification preferences.
 * - Updates existing assignments' notification preferences.
 * - Handles can_notify flag correctly (true/false/missing).
 * - Processes multiple organizations in one invocation.
 * - Defaults to false when can_notify is not provided.
 */
class AssignTrooperNotificationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_new_assignment_with_notification_enabled(): void
    {
        // Arrange
        $subject = new AssignTrooperNotificationsCommand();

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $organizations_data = [
            $organization->id => [
                'can_notify' => true,
            ],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $assignment = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $organization->id)
            ->first();

        $this->assertNotNull($assignment);
        $this->assertTrue($assignment->can_notify);
    }

    public function test_invoke_creates_new_assignment_with_notification_disabled(): void
    {
        // Arrange
        $subject = new AssignTrooperNotificationsCommand();

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $organizations_data = [
            $organization->id => [
                'can_notify' => false,
            ],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $assignment = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $organization->id)
            ->first();

        $this->assertNotNull($assignment);
        $this->assertFalse($assignment->can_notify);
    }

    public function test_invoke_updates_existing_assignment_notification_preference(): void
    {
        // Arrange
        $subject = new AssignTrooperNotificationsCommand();

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $existing_assignment = TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::CAN_NOTIFY => false,
        ]);

        $organizations_data = [
            $organization->id => [
                'can_notify' => true,
            ],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $existing_assignment->refresh();
        $this->assertTrue($existing_assignment->can_notify);
    }

    public function test_invoke_defaults_to_false_when_can_notify_not_provided(): void
    {
        // Arrange
        $subject = new AssignTrooperNotificationsCommand();

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
        $this->assertFalse($assignment->can_notify);
    }

    public function test_invoke_processes_multiple_organizations(): void
    {
        // Arrange
        $subject = new AssignTrooperNotificationsCommand();

        $trooper = Trooper::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org3 = Organization::factory()->create();

        $organizations_data = [
            $org1->id => ['can_notify' => true],
            $org2->id => ['can_notify' => false],
            $org3->id => ['can_notify' => true],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $this->assertEquals(3, $trooper->trooper_assignments()->count());

        $assignment1 = $trooper->trooper_assignments()
            ->where(TrooperAssignment::ORGANIZATION_ID, $org1->id)
            ->first();
        $this->assertTrue($assignment1->can_notify);

        $assignment2 = $trooper->trooper_assignments()
            ->where(TrooperAssignment::ORGANIZATION_ID, $org2->id)
            ->first();
        $this->assertFalse($assignment2->can_notify);

        $assignment3 = $trooper->trooper_assignments()
            ->where(TrooperAssignment::ORGANIZATION_ID, $org3->id)
            ->first();
        $this->assertTrue($assignment3->can_notify);
    }

    public function test_invoke_updates_can_notify_from_true_to_false(): void
    {
        // Arrange
        $subject = new AssignTrooperNotificationsCommand();

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $existing_assignment = TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::CAN_NOTIFY => true,
        ]);

        $organizations_data = [
            $organization->id => [
                'can_notify' => false,
            ],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $existing_assignment->refresh();
        $this->assertFalse($existing_assignment->can_notify);
    }

    public function test_invoke_preserves_other_assignment_attributes(): void
    {
        // Arrange
        $subject = new AssignTrooperNotificationsCommand();

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $existing_assignment = TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::CAN_NOTIFY => false,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        $organizations_data = [
            $organization->id => [
                'can_notify' => true,
            ],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $existing_assignment->refresh();
        $this->assertTrue($existing_assignment->can_notify);
        $this->assertTrue($existing_assignment->is_member);
        $this->assertEquals($trooper->id, $existing_assignment->trooper_id);
        $this->assertEquals($organization->id, $existing_assignment->organization_id);
    }

    public function test_invoke_handles_mixed_new_and_existing_assignments(): void
    {
        // Arrange
        $subject = new AssignTrooperNotificationsCommand();

        $trooper = Trooper::factory()->create();
        $existing_org = Organization::factory()->create();
        $new_org = Organization::factory()->create();

        // Create existing assignment
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $existing_org->id,
            TrooperAssignment::CAN_NOTIFY => false,
        ]);

        $organizations_data = [
            $existing_org->id => ['can_notify' => true],
            $new_org->id => ['can_notify' => false],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert
        $this->assertEquals(2, $trooper->trooper_assignments()->count());

        $existing_assignment = $trooper->trooper_assignments()
            ->where(TrooperAssignment::ORGANIZATION_ID, $existing_org->id)
            ->first();
        $this->assertTrue($existing_assignment->can_notify);

        $new_assignment = $trooper->trooper_assignments()
            ->where(TrooperAssignment::ORGANIZATION_ID, $new_org->id)
            ->first();
        $this->assertFalse($new_assignment->can_notify);
    }

    public function test_invoke_disables_notifications_for_assignments_not_in_update_list(): void
    {
        // Arrange
        $subject = new AssignTrooperNotificationsCommand();

        $trooper = Trooper::factory()->create();
        $org_to_update = Organization::factory()->create();
        $org_to_disable = Organization::factory()->create();

        // Create two existing assignments, both with notifications enabled
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org_to_update->id,
            TrooperAssignment::CAN_NOTIFY => true,
        ]);

        $assignment_to_disable = TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org_to_disable->id,
            TrooperAssignment::CAN_NOTIFY => true,
        ]);

        // Only provide data for one organization
        $organizations_data = [
            $org_to_update->id => ['can_notify' => true],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert - assignment not in the update list should have notifications disabled
        $assignment_to_disable->refresh();
        $this->assertFalse($assignment_to_disable->can_notify);

        // Assignment in the update list should still be enabled
        $updated_assignment = $trooper->trooper_assignments()
            ->where(TrooperAssignment::ORGANIZATION_ID, $org_to_update->id)
            ->first();
        $this->assertTrue($updated_assignment->can_notify);
    }

    public function test_invoke_resets_all_assignments_before_applying_new_preferences(): void
    {
        // Arrange
        $subject = new AssignTrooperNotificationsCommand();

        $trooper = Trooper::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org3 = Organization::factory()->create();

        // Create three assignments, all with notifications enabled
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org1->id,
            TrooperAssignment::CAN_NOTIFY => true,
        ]);

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org2->id,
            TrooperAssignment::CAN_NOTIFY => true,
        ]);

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org3->id,
            TrooperAssignment::CAN_NOTIFY => true,
        ]);

        // Only enable notifications for org1
        $organizations_data = [
            $org1->id => ['can_notify' => true],
        ];

        // Act
        $subject($trooper, $organizations_data);

        // Assert - only org1 should have notifications enabled
        $assignments = $trooper->trooper_assignments()->get();

        $org1_assignment = $assignments->firstWhere(TrooperAssignment::ORGANIZATION_ID, $org1->id);
        $this->assertTrue($org1_assignment->can_notify);

        $org2_assignment = $assignments->firstWhere(TrooperAssignment::ORGANIZATION_ID, $org2->id);
        $this->assertFalse($org2_assignment->can_notify);

        $org3_assignment = $assignments->firstWhere(TrooperAssignment::ORGANIZATION_ID, $org3->id);
        $this->assertFalse($org3_assignment->can_notify);
    }
}
