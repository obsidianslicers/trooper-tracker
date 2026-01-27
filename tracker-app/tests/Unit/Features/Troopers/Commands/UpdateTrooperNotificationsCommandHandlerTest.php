<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Commands;

use App\Features\Troopers\Commands\UpdateTrooperNotificationsCommand;
use App\Features\Troopers\Commands\UpdateTrooperNotificationsCommandHandler;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for UpdateTrooperNotificationsCommandHandler.
 *
 * Verifies:
 * - Resets all existing assignments to should_notify = false
 * - Creates new assignments with should_notify flag
 * - Updates existing assignments
 */
class UpdateTrooperNotificationsCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_resets_existing_assignments_to_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);

        $command = new UpdateTrooperNotificationsCommand($trooper, []);
        $subject = new UpdateTrooperNotificationsCommandHandler();

        // Act
        $subject($command);

        // Assert
        $assignment = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)->first();
        $this->assertFalse($assignment->should_notify);
    }

    public function test_invoke_creates_new_assignment_with_should_notify_true(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $valid_data = [$organization->id => ['should_notify' => true]];
        $command = new UpdateTrooperNotificationsCommand($trooper, $valid_data);
        $subject = new UpdateTrooperNotificationsCommandHandler();

        // Act
        $subject($command);

        // Assert
        $assignment = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $organization->id)
            ->first();
        $this->assertNotNull($assignment);
        $this->assertTrue($assignment->should_notify);
    }

    public function test_invoke_updates_existing_assignment(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $existing = TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::SHOULD_NOTIFY => false,
        ]);

        $valid_data = [$organization->id => ['should_notify' => true]];
        $command = new UpdateTrooperNotificationsCommand($trooper, $valid_data);
        $subject = new UpdateTrooperNotificationsCommandHandler();

        // Act
        $subject($command);

        // Assert
        $existing->refresh();
        $this->assertTrue($existing->should_notify);
    }

    public function test_invoke_returns_null(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $command = new UpdateTrooperNotificationsCommand($trooper, []);
        $subject = new UpdateTrooperNotificationsCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertNull($result);
    }
}
