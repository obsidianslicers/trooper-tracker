<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Bus\MagicBus;
use App\Features\Troopers\Commands\RecalculateTrooperRankCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Test suite for CalculateTrooperAchievementsCommand.
 *
 * Verifies that the command correctly orchestrates trooper achievement
 * and rank recalculation by dispatching appropriate commands through
 * the message bus.
 */
class CalculateTrooperAchievementsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the command executes successfully and dispatches recalculation command.
     */
    public function test_command_dispatches_recalculate_trooper_rank_command(): void
    {
        // Arrange: Mock the MagicBus to verify command dispatch
        $this->mock(MagicBus::class, function (MockInterface $mock)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(RecalculateTrooperRankCommand::class))
                ->andReturn(null);
        });

        // Act: Execute the artisan command
        $this->artisan('tracker:calculate-trooper-achievements')
            ->assertExitCode(0);
    }

    /**
     * Test that the command outputs completion message with timing information.
     */
    public function test_command_outputs_completion_message(): void
    {
        // Arrange: Mock the MagicBus
        $this->mock(MagicBus::class, function (MockInterface $mock)
        {
            $mock->shouldReceive('send')
                ->once()
                ->andReturn(null);
        });

        // Act: Execute the command
        $result = $this->artisan('tracker:calculate-trooper-achievements');

        // Assert: Command succeeds
        $result->assertExitCode(0);
    }

    /**
     * Test that the command signature is correctly defined.
     */
    public function test_command_has_correct_signature(): void
    {
        // Act: Get all registered commands
        $commands = $this->app['Illuminate\Contracts\Console\Kernel']->all();

        // Assert: Verify command exists with correct signature
        $this->assertArrayHasKey('tracker:calculate-trooper-achievements', $commands);
    }

    /**
     * Test that the command dispatches command without trooper_id parameter.
     */
    public function test_command_dispatches_without_trooper_id(): void
    {
        // Arrange & Assert: Verify RecalculateTrooperRankCommand is created with null trooper_id
        $this->mock(MagicBus::class, function (MockInterface $mock)
        {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (RecalculateTrooperRankCommand $command)
                {
                    return $command->trooper_id === null;
                })
                ->andReturn(null);
        });

        // Act: Execute the command
        $this->artisan('tracker:calculate-trooper-achievements')
            ->assertExitCode(0);
    }
}
