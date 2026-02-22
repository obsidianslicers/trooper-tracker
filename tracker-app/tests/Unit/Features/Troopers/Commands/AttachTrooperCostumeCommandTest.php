<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Commands;

use App\Features\Troopers\Commands\AttachTrooperCostumeCommand;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for AttachTrooperCostumeCommand.
 *
 * Verifies:
 * - Command construction with parameters
 * - Property access
 */
class AttachTrooperCostumeCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_construct_with_parameters(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization_costume_ids = [1, 2];

        // Act
        $subject = new AttachTrooperCostumeCommand($trooper, $organization_costume_ids);

        // Assert
        $this->assertSame($trooper, $subject->trooper);
        $this->assertSame($organization_costume_ids, $subject->organization_ids);
    }
}
