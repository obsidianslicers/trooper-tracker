<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Enums\NotificationFrequency;
use App\Features\Events\Commands\SendEventCreatedNotificationCommand;
use App\Features\Events\Commands\SendEventCreatedNotificationCommandHandler;
use App\Models\Event;
use App\Models\EventNotification;
use App\Models\Trooper;
use App\Notifications\Events\EventCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @see SendEventCreatedNotificationCommandHandler
 */
class SendEventCreatedNotificationCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_sends_notification_for_instant_preference(): void
    {
        Notification::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT,
        ]);

        $command = new SendEventCreatedNotificationCommand(
            event: $event,
            trooper: $trooper
        );
        $handler = app(SendEventCreatedNotificationCommandHandler::class);

        $handler($command);

        Notification::assertSentTo($trooper, EventCreatedNotification::class);
    }

    public function test_invoke_sends_notification_for_daily_preference(): void
    {
        Notification::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);

        $command = new SendEventCreatedNotificationCommand(
            event: $event,
            trooper: $trooper
        );
        $handler = app(SendEventCreatedNotificationCommandHandler::class);

        $handler($command);

        Notification::assertSentTo($trooper, EventCreatedNotification::class);
    }

    public function test_invoke_creates_unprocessed_event_notification_for_daily_preference(): void
    {
        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
            Trooper::EMAIL => 'valid@example.com',
        ]);

        $command = new SendEventCreatedNotificationCommand(
            event: $event,
            trooper: $trooper
        );
        $handler = app(SendEventCreatedNotificationCommandHandler::class);

        $handler($command);

        $notification = EventNotification::where(EventNotification::TROOPER_ID, $trooper->id)->first();
        $this->assertNotNull($notification);
        $this->assertNull($notification->{EventNotification::PROCESSED_AT});
    }

    public function test_invoke_creates_processed_event_notification_for_instant_preference(): void
    {
        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT,
            Trooper::EMAIL => 'valid@example.com',
        ]);

        $command = new SendEventCreatedNotificationCommand(
            event: $event,
            trooper: $trooper
        );
        $handler = app(SendEventCreatedNotificationCommandHandler::class);

        $handler($command);

        $notification = EventNotification::where(EventNotification::TROOPER_ID, $trooper->id)->first();
        $this->assertNotNull($notification);
        $this->assertNotNull($notification->{EventNotification::PROCESSED_AT});
    }

    public function test_invoke_sends_notification_even_when_email_invalid(): void
    {
        Notification::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create([
            Trooper::EMAIL => '',
        ]);

        $command = new SendEventCreatedNotificationCommand(
            event: $event,
            trooper: $trooper
        );
        $handler = app(SendEventCreatedNotificationCommandHandler::class);

        $handler($command);

        Notification::assertSentTo($trooper, EventCreatedNotification::class);
    }
}
