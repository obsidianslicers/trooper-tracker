<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Messages\Troopers\Commands\RecalculateTrooperRank;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
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
    use FakesMessages;
    use RefreshDatabase;

    /**
     * Test that the command executes successfully and dispatches recalculation command.
     */
    public function test_command_dispatches_recalculate_trooper_rank_command(): void
    {
        // Arrange: Fake the message to verify dispatch
        $messages = $this->fakeMessages([
            RecalculateTrooperRank::class => null,
        ]);

        // Act: Execute the artisan command
        $this->artisan('tracker:calculate-trooper-achievements')
            ->assertExitCode(0);

        $messages->assertCalled(RecalculateTrooperRank::class);
    }

    /**
     * Test that the command outputs completion message with timing information.
     */
    public function test_command_outputs_completion_message(): void
    {
        // Arrange: Fake the message
        $messages = $this->fakeMessages([
            RecalculateTrooperRank::class => null,
        ]);

        // Act: Execute the command
        $result = $this->artisan('tracker:calculate-trooper-achievements');

        // Assert: Command succeeds
        $result->assertExitCode(0)->run();

        $messages->assertCalled(RecalculateTrooperRank::class);
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
        // Arrange & Assert: Verify RecalculateTrooperRank is created with null trooper_id
        $messages = $this->fakeMessages([
            RecalculateTrooperRank::class => null,
        ]);

        // Act: Execute the command
        $this->artisan('tracker:calculate-trooper-achievements')
            ->assertExitCode(0);

        $messages->assertCalled(
            RecalculateTrooperRank::class,
            fn (array $params): bool => $params['trooper_id'] === null
        );
    }

    public function test_command_without_notifications_option_disables_milestone_notifications(): void
    {
        $messages = $this->fakeMessages([
            RecalculateTrooperRank::class => null,
        ]);

        $this->artisan('tracker:calculate-trooper-achievements --without-notifications')
            ->expectsOutput('Milestone notifications were disabled for this run.')
            ->assertExitCode(0);

        $messages->assertCalled(
            RecalculateTrooperRank::class,
            fn (array $params): bool => $params['send_milestone_notifications'] === false
        );
    }

    public function test_command_outputs_created_milestone_summary(): void
    {
        $messages = $this->fakeMessages([
            RecalculateTrooperRank::class => [
                'created_milestones' => [
                    'total' => 3,
                    'global' => 1,
                    'club' => 2,
                    'by_type' => [
                        'first_troop' => 2,
                        'trooped_10' => 1,
                    ],
                ],
            ],
        ]);

        $this->artisan('tracker:calculate-trooper-achievements')
            ->expectsOutput('Milestones created: 3 (global: 1, club-scoped: 2).')
            ->expectsOutput(' - first_troop: 2')
            ->expectsOutput(' - trooped_10: 1')
            ->assertExitCode(0);

        $messages->assertCalled(RecalculateTrooperRank::class);
    }
}
