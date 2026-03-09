<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Bus\MagicBus;
use App\Features\Events\Commands\SendEventDailyNotificationCommand;
use App\Features\Events\Queries\GetTroopersForDailyEventNotificationsQuery;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Test suite for SendDailyEventNotifications command.
 *
 * Verifies that the command correctly identifies troopers who need
 * daily event notification digests and dispatches notification commands
 * for each trooper.
 */
class SendDailyEventNotificationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the command retrieves troopers and sends notifications.
     */
    public function test_command_retrieves_troopers_and_sends_notifications(): void
    {
        // Arrange: Create test troopers
        $trooper1 = Trooper::factory()->create();
        $trooper2 = Trooper::factory()->create();
        $troopers = collect([$trooper1, $trooper2]);

        // Mock MagicBus
        $this->mock(MagicBus::class, function (MockInterface $mock) use ($troopers, $trooper1, $trooper2)
        {
            // First call: get troopers query
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetTroopersForDailyEventNotificationsQuery::class))
                ->andReturn($troopers);

            // Second call: send notification for trooper1
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (SendEventDailyNotificationCommand $command) use ($trooper1)
                {
                    return $command->trooper->id === $trooper1->id;
                })
                ->andReturn(null);

            // Third call: send notification for trooper2
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (SendEventDailyNotificationCommand $command) use ($trooper2)
                {
                    return $command->trooper->id === $trooper2->id;
                })
                ->andReturn(null);
        });

        // Act: Execute the command
        $this->artisan('tracker:send-daily-event-notifications')
            ->assertExitCode(0);
    }

    /**
     * Test that the command handles empty trooper collection gracefully.
     */
    public function test_command_handles_no_troopers_to_notify(): void
    {
        // Arrange: Mock MagicBus to return empty collection
        $this->mock(MagicBus::class, function (MockInterface $mock)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetTroopersForDailyEventNotificationsQuery::class))
                ->andReturn(collect([]));
        });

        // Act & Assert: Command should complete successfully without sending notifications
        $this->artisan('tracker:send-daily-event-notifications')
            ->assertExitCode(0);
    }

    /**
     * Test that the command dispatches notification for each trooper.
     */
    public function test_command_dispatches_notification_for_each_trooper(): void
    {
        // Arrange: Create multiple troopers
        $trooper1 = Trooper::factory()->create();
        $trooper2 = Trooper::factory()->create();
        $trooper3 = Trooper::factory()->create();
        $troopers = collect([$trooper1, $trooper2, $trooper3]);

        // Mock MagicBus
        $this->mock(MagicBus::class, function (MockInterface $mock) use ($troopers)
        {
            // Get troopers query
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetTroopersForDailyEventNotificationsQuery::class))
                ->andReturn($troopers);

            // Verify notification command sent for each trooper
            $mock->shouldReceive('send')
                ->times(3)
                ->with(Mockery::type(SendEventDailyNotificationCommand::class))
                ->andReturn(null);
        });

        // Act: Execute the command
        $this->artisan('tracker:send-daily-event-notifications')
            ->assertExitCode(0);
    }

    /**
     * Test that the command dispatches the correct query.
     */
    public function test_command_dispatches_get_troopers_for_daily_notifications_query(): void
    {
        // Arrange & Assert: Verify correct query is sent
        $this->mock(MagicBus::class, function (MockInterface $mock)
        {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function ($query)
                {
                    return $query instanceof GetTroopersForDailyEventNotificationsQuery;
                })
                ->andReturn(collect([]));
        });

        // Act: Execute the command
        $this->artisan('tracker:send-daily-event-notifications')
            ->assertExitCode(0);
    }

    /**
     * Test that the command has the correct signature.
     */
    public function test_command_has_correct_signature(): void
    {
        // Act: Get all registered commands
        $commands = $this->app['Illuminate\Contracts\Console\Kernel']->all();

        // Assert: Verify command exists
        $this->assertArrayHasKey('tracker:send-daily-event-notifications', $commands);
    }

    /**
     * Test that the command sends notifications in correct order.
     */
    public function test_command_sends_notifications_in_order(): void
    {
        // Arrange: Create troopers
        $trooper1 = Trooper::factory()->create([Trooper::EMAIL => 'first@example.com']);
        $trooper2 = Trooper::factory()->create([Trooper::EMAIL => 'second@example.com']);
        $troopers = collect([$trooper1, $trooper2]);

        $notification_order = [];

        // Mock MagicBus
        $this->mock(MagicBus::class, function (MockInterface $mock) use ($troopers, &$notification_order)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetTroopersForDailyEventNotificationsQuery::class))
                ->andReturn($troopers);

            $mock->shouldReceive('send')
                ->twice()
                ->with(Mockery::type(SendEventDailyNotificationCommand::class))
                ->andReturnUsing(function ($command) use (&$notification_order)
                {
                    $notification_order[] = $command->trooper->email;
                    return null;
                });
        });

        // Act: Execute the command
        $this->artisan('tracker:send-daily-event-notifications')
            ->assertExitCode(0);

        // Assert: Notifications sent in order
        $this->assertEquals(['first@example.com', 'second@example.com'], $notification_order);
    }

    /**
     * Test that the command creates SendEventDailyNotificationCommand with correct trooper.
     */
    public function test_command_creates_notification_command_with_correct_trooper(): void
    {
        // Arrange: Create specific trooper
        $trooper = Trooper::factory()->create([
            Trooper::EMAIL => 'test@example.com',
            Trooper::DISPLAY_NAME => 'Test Trooper',
        ]);
        $troopers = collect([$trooper]);

        // Mock MagicBus and capture the command
        $this->mock(MagicBus::class, function (MockInterface $mock) use ($troopers, $trooper)
        {
            $mock->shouldReceive('send')
                ->once()
                ->with(Mockery::type(GetTroopersForDailyEventNotificationsQuery::class))
                ->andReturn($troopers);

            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (SendEventDailyNotificationCommand $command) use ($trooper)
                {
                    return $command->trooper->id === $trooper->id &&
                        $command->trooper->email === $trooper->email &&
                        $command->trooper->display_name === $trooper->display_name;
                })
                ->andReturn(null);
        });

        // Act: Execute the command
        $this->artisan('tracker:send-daily-event-notifications')
            ->assertExitCode(0);
    }
}
