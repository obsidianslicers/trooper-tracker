<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Commands;

use App\Features\Troopers\Commands\UpdateTrooperNotificationsCommand;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for UpdateTrooperNotificationsCommand.
 *
 * Verifies:
 * - Command construction with trooper and data
 * - Property access
 */
class UpdateTrooperNotificationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_construct_with_trooper_and_data(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $valid_data = [1 => ['can_notify' => true], 2 => ['can_notify' => false]];

        // Act
        $subject = new UpdateTrooperNotificationsCommand($trooper, $valid_data);

        // Assert
        $this->assertSame($trooper, $subject->trooper);
        $this->assertSame($valid_data, $subject->valid_data);
    }
}
