<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Commands;

use App\Features\Troopers\Commands\DetachTrooperCostumeCommand;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for DetachTrooperCostumeCommand.
 *
 * Verifies:
 * - Command construction with parameters
 * - Property access
 */
class DetachTrooperCostumeCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_construct_with_parameters(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $subject = new DetachTrooperCostumeCommand($trooper, 42);

        // Assert
        $this->assertSame($trooper, $subject->trooper);
        $this->assertEquals(42, $subject->costume_id);
    }
}
