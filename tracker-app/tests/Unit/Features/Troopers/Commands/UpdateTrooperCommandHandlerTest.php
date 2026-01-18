<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Commands;

use App\Features\Troopers\Commands\UpdateTrooperCommand;
use App\Features\Troopers\Commands\UpdateTrooperCommandHandler;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for UpdateTrooperCommandHandler.
 *
 * Verifies:
 * - Updates trooper with valid data
 * - Sets setup_completed_at when complete_setup is true
 * - Does not set setup_completed_at when complete_setup is false
 */
class UpdateTrooperCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_trooper_attributes(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::NAME => 'Old Name',
        ]);

        $valid_data = [Trooper::NAME => 'New Name'];
        $command = new UpdateTrooperCommand($trooper, $valid_data);
        $subject = new UpdateTrooperCommandHandler();

        // Act
        $subject($command);

        // Assert
        $trooper->refresh();
        $this->assertEquals('New Name', $trooper->name);
    }

    public function test_invoke_sets_setup_completed_at_when_complete_setup_is_true(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::SETUP_COMPLETED_AT => null,
        ]);

        $valid_data = [Trooper::NAME => 'Test Name'];
        $command = new UpdateTrooperCommand($trooper, $valid_data, true);
        $subject = new UpdateTrooperCommandHandler();

        // Act
        $subject($command);

        // Assert
        $trooper->refresh();
        $this->assertNotNull($trooper->setup_completed_at);
    }

    public function test_invoke_does_not_set_setup_completed_at_when_complete_setup_is_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::SETUP_COMPLETED_AT => null,
        ]);

        $valid_data = [Trooper::NAME => 'Test Name'];
        $command = new UpdateTrooperCommand($trooper, $valid_data, false);
        $subject = new UpdateTrooperCommandHandler();

        // Act
        $subject($command);

        // Assert
        $trooper->refresh();
        $this->assertNull($trooper->setup_completed_at);
    }

    public function test_invoke_returns_null(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $command = new UpdateTrooperCommand($trooper, []);
        $subject = new UpdateTrooperCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertNull($result);
    }
}
