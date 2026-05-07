<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Features\Events\Commands\SendEventCancelledNotificationCommand;
use App\Features\Events\Commands\SendEventCancelledNotificationCommandHandler;
use App\Models\Event;
use App\Models\Trooper;
use App\Notifications\Events\EventCancelledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @see SendEventCancelledNotificationCommandHandler
 */
class SendEventCancelledNotificationCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_sends_cancellation_notification(): void
    {
        Notification::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create();

        $command = new SendEventCancelledNotificationCommand(
            event: $event,
            trooper: $trooper
        );
        $handler = app(SendEventCancelledNotificationCommandHandler::class);

        $handler($command);

        Notification::assertSentTo($trooper, EventCancelledNotification::class);
    }

    public function test_invoke_sends_notification_even_when_email_invalid(): void
    {
        Notification::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create([
            Trooper::EMAIL => '',
        ]);

        $command = new SendEventCancelledNotificationCommand(
            event: $event,
            trooper: $trooper
        );
        $handler = app(SendEventCancelledNotificationCommandHandler::class);

        $handler($command);

        Notification::assertSentTo($trooper, EventCancelledNotification::class);
    }

    public function test_invoke_returns_null(): void
    {
        Notification::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create();

        $command = new SendEventCancelledNotificationCommand(
            event: $event,
            trooper: $trooper
        );
        $handler = app(SendEventCancelledNotificationCommandHandler::class);

        $result = $handler($command);

        $this->assertNull($result);
    }
}
