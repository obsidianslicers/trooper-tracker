<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Commands;

use App\Features\Troopers\Commands\AttachTrooperCostumeCommand;
use App\Features\Troopers\Commands\AttachTrooperCostumeCommandHandler;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for AttachTrooperCostumeCommandHandler.
 *
 * Verifies:
 * - Creates TrooperCostume when costume is valid
 * - Restores soft-deleted TrooperCostume if exists
 * - Validates organization access
 * - Ignores organization costumes without access
 */
class AttachTrooperCostumeCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_trooper_costume(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $organization_costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
        ]);

        // Create assignment so trooper has access
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        $command = new AttachTrooperCostumeCommand($trooper, [$organization_costume->id]);
        $subject = new AttachTrooperCostumeCommandHandler();

        // Act
        $subject($command);

        // Assert
        $trooper_costume = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper->id)
            ->where(TrooperCostume::ORGANIZATION_COSTUME_ID, $organization_costume->id)
            ->first();
        $this->assertNotNull($trooper_costume);
    }

    public function test_invoke_restores_soft_deleted_costume(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $organization_costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
        ]);

        $soft_deleted = TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $organization_costume->id,
        ]);
        $soft_deleted->delete();

        // Create assignment
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        $command = new AttachTrooperCostumeCommand($trooper, [$organization_costume->id]);
        $subject = new AttachTrooperCostumeCommandHandler();

        // Act
        $subject($command);

        // Assert
        $soft_deleted->refresh();
        $this->assertNull($soft_deleted->deleted_at);
    }

    public function test_invoke_does_nothing_when_organization_not_accessible(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $organization_costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
        ]);

        // No assignment - trooper has no access

        $command = new AttachTrooperCostumeCommand($trooper, [$organization_costume->id]);
        $subject = new AttachTrooperCostumeCommandHandler();

        // Act
        $subject($command);

        // Assert
        $this->assertEquals(0, TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper->id)->count());
    }

    public function test_invoke_returns_null(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $command = new AttachTrooperCostumeCommand($trooper, []);
        $subject = new AttachTrooperCostumeCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertNull($result);
    }
}
