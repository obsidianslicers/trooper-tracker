<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Commands;

use App\Features\Troopers\Commands\UpdateTrooperMembershipsCommand;
use App\Features\Troopers\Commands\UpdateTrooperMembershipsCommandHandler;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for UpdateTrooperMembershipsCommandHandler.
 *
 * Verifies:
 * - Creates new assignments with is_member = true
 * - Updates existing assignments
 * - Skips entries without assignment ID
 */
class UpdateTrooperMembershipsCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_new_assignment(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $valid_data = [1 => ['assignment' => $organization->id]];
        $command = new UpdateTrooperMembershipsCommand($trooper, $valid_data);
        $subject = new UpdateTrooperMembershipsCommandHandler();

        // Act
        $subject($command);

        // Assert
        $assignment = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $organization->id)
            ->first();
        $this->assertNotNull($assignment);
        $this->assertTrue($assignment->is_member);
    }

    public function test_invoke_updates_existing_assignment(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $existing = TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => false,
        ]);

        // Load the trooper_assignments relationship
        $trooper->load('trooper_assignments');

        $valid_data = [1 => ['assignment' => $organization->id]];
        $command = new UpdateTrooperMembershipsCommand($trooper, $valid_data);
        $subject = new UpdateTrooperMembershipsCommandHandler();

        // Act
        $subject($command);

        // Assert
        $existing->refresh();
        $this->assertTrue($existing->is_member);
    }

    public function test_invoke_skips_entries_without_assignment(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        $valid_data = [1 => [], 2 => ['assignment' => null]];
        $command = new UpdateTrooperMembershipsCommand($trooper, $valid_data);
        $subject = new UpdateTrooperMembershipsCommandHandler();

        // Act
        $subject($command);

        // Assert
        $this->assertEquals(0, TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)->count());
    }

    public function test_invoke_returns_null(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $command = new UpdateTrooperMembershipsCommand($trooper, []);
        $subject = new UpdateTrooperMembershipsCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertNull($result);
    }
}
