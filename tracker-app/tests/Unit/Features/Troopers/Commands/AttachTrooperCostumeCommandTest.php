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

        // Act
        $subject = new AttachTrooperCostumeCommand($trooper, 1, 2);

        // Assert
        $this->assertSame($trooper, $subject->trooper);
        $this->assertEquals(1, $subject->organization_id);
        $this->assertEquals(2, $subject->costume_id);
    }
}
