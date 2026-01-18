<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Commands;

use App\Features\Troopers\Commands\UpdateTrooperCommand;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for UpdateTrooperCommand.
 *
 * Verifies:
 * - Command construction with default values
 * - Command construction with complete_setup flag
 * - Property access
 */
class UpdateTrooperCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_construct_with_default_values(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $valid_data = ['name' => 'Test Name'];

        // Act
        $subject = new UpdateTrooperCommand($trooper, $valid_data);

        // Assert
        $this->assertSame($trooper, $subject->trooper);
        $this->assertSame($valid_data, $subject->valid_data);
        $this->assertFalse($subject->complete_setup);
    }

    public function test_construct_with_complete_setup_true(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $valid_data = ['name' => 'Test Name'];

        // Act
        $subject = new UpdateTrooperCommand($trooper, $valid_data, true);

        // Assert
        $this->assertTrue($subject->complete_setup);
    }
}
