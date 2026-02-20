<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Commands;

use App\Features\Troopers\Commands\DetachTrooperCostumeCommand;
use App\Features\Troopers\Commands\DetachTrooperCostumeCommandHandler;
use App\Models\Costume;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for DetachTrooperCostumeCommandHandler.
 *
 * Verifies:
 * - Soft-deletes TrooperCostume
 * - Does nothing if costume not found (idempotent)
 * - Returns null
 */
class DetachTrooperCostumeCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_soft_deletes_trooper_costume(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $costume = Costume::factory()->create();
        $org_costume = OrganizationCostume::factory()->create([
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        $trooper_costume = TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
        ]);

        $command = new DetachTrooperCostumeCommand($trooper, $costume->id);
        $subject = new DetachTrooperCostumeCommandHandler();

        // Act
        $subject($command);

        // Assert
        $trooper_costume->refresh();
        $this->assertNotNull($trooper_costume->deleted_at);
    }

    public function test_invoke_is_idempotent_when_costume_not_found(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $command = new DetachTrooperCostumeCommand($trooper, 999);
        $subject = new DetachTrooperCostumeCommandHandler();

        // Act & Assert - should not throw exception
        $result = $subject($command);
        $this->assertNull($result);
    }

    public function test_invoke_returns_null(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $command = new DetachTrooperCostumeCommand($trooper, 1);
        $subject = new DetachTrooperCostumeCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertNull($result);
    }
}
