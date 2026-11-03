<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Messages\Events\Commands\SendEventDailyNotification;
use App\Messages\Events\Queries\GetTroopersForDailyEventNotifications;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
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
    use FakesMessages;
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

        // Fake the messages
        $messages = $this->fakeMessages([
            GetTroopersForDailyEventNotifications::class => $troopers,
            SendEventDailyNotification::class => fn (array $params): mixed => match (true)
            {
                $params['trooper']->id === $trooper1->id => null,
                $params['trooper']->id === $trooper2->id => null,
            },
        ]);

        // Act: Execute the command
        $this->artisan('tracker:send-daily-event-notifications')
            ->assertExitCode(0);

        $messages->assertCalled(GetTroopersForDailyEventNotifications::class);
        $messages->assertCalled(
            SendEventDailyNotification::class,
            fn (array $params): bool => $params['trooper']->id === $trooper1->id
        );
        $messages->assertCalled(
            SendEventDailyNotification::class,
            fn (array $params): bool => $params['trooper']->id === $trooper2->id
        );
    }

    /**
     * Test that the command handles empty trooper collection gracefully.
     */
    public function test_command_handles_no_troopers_to_notify(): void
    {
        // Arrange: Fake the query to return empty collection
        $messages = $this->fakeMessages([
            GetTroopersForDailyEventNotifications::class => collect([]),
        ]);

        // Act & Assert: Command should complete successfully without sending notifications
        $this->artisan('tracker:send-daily-event-notifications')
            ->assertExitCode(0);

        $messages->assertCalled(GetTroopersForDailyEventNotifications::class);
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

        // Fake the messages
        $messages = $this->fakeMessages([
            GetTroopersForDailyEventNotifications::class => $troopers,
            SendEventDailyNotification::class => null,
        ]);

        // Act: Execute the command
        $this->artisan('tracker:send-daily-event-notifications')
            ->assertExitCode(0);

        $messages->assertCalled(GetTroopersForDailyEventNotifications::class);
        $messages->assertCalled(SendEventDailyNotification::class, null, times: 3);
    }

    /**
     * Test that the command dispatches the correct query.
     */
    public function test_command_dispatches_get_troopers_for_daily_notifications_query(): void
    {
        // Arrange & Assert: Verify correct query is sent
        $messages = $this->fakeMessages([
            GetTroopersForDailyEventNotifications::class => collect([]),
        ]);

        // Act: Execute the command
        $this->artisan('tracker:send-daily-event-notifications')
            ->assertExitCode(0);

        $messages->assertCalled(GetTroopersForDailyEventNotifications::class);
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

        // Fake the messages
        $messages = $this->fakeMessages([
            GetTroopersForDailyEventNotifications::class => $troopers,
            SendEventDailyNotification::class => function (array $params) use (&$notification_order): null
            {
                $notification_order[] = $params['trooper']->email;

                return null;
            },
        ]);

        // Act: Execute the command
        $this->artisan('tracker:send-daily-event-notifications')
            ->assertExitCode(0);

        $messages->assertCalled(GetTroopersForDailyEventNotifications::class);
        $messages->assertCalled(SendEventDailyNotification::class, null, times: 2);

        // Assert: Notifications sent in order
        $this->assertEquals(['first@example.com', 'second@example.com'], $notification_order);
    }

    /**
     * Test that the command creates SendEventDailyNotification with correct trooper.
     */
    public function test_command_creates_notification_command_with_correct_trooper(): void
    {
        // Arrange: Create specific trooper
        $trooper = Trooper::factory()->create([
            Trooper::EMAIL => 'test@example.com',
            Trooper::DISPLAY_NAME => 'Test Trooper',
        ]);
        $troopers = collect([$trooper]);

        // Fake the messages and capture the call
        $messages = $this->fakeMessages([
            GetTroopersForDailyEventNotifications::class => $troopers,
            SendEventDailyNotification::class => null,
        ]);

        // Act: Execute the command
        $this->artisan('tracker:send-daily-event-notifications')
            ->assertExitCode(0);

        $messages->assertCalled(GetTroopersForDailyEventNotifications::class);
        $messages->assertCalled(
            SendEventDailyNotification::class,
            fn (array $params): bool => $params['trooper']->id === $trooper->id && $params['trooper']->email === $trooper->email && $params['trooper']->display_name === $trooper->display_name
        );
    }
}
