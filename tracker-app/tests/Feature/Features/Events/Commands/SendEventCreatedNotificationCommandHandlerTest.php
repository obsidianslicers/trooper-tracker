<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Enums\NotificationFrequency;
use App\Features\Events\Commands\SendEventCreatedNotificationCommand;
use App\Features\Events\Commands\SendEventCreatedNotificationCommandHandler;
use App\Mail\Events\InstantEventNotification;
use App\Models\Event;
use App\Models\EventNotification;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * @see SendEventCreatedNotificationCommandHandler
 */
class SendEventCreatedNotificationCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_event_notification_for_instant_preference(): void
    {
        Mail::fake();

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

        $this->assertDatabaseHas('tt_event_notifications', [
            EventNotification::EVENT_ID => $event->id,
            EventNotification::TROOPER_ID => $trooper->id,
        ]);
    }

    public function test_invoke_marks_notification_processed_for_instant_preference(): void
    {
        Mail::fake();

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

        $notification = EventNotification::where(EventNotification::TROOPER_ID, $trooper->id)->first();
        $this->assertNotNull($notification->{EventNotification::PROCESSED_AT});
    }

    public function test_invoke_queues_instant_email_for_instant_preference(): void
    {
        Mail::fake();

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

        Mail::assertQueued(InstantEventNotification::class, function ($mail) use ($trooper)
        {
            return $mail->hasTo($trooper->{Trooper::EMAIL});
        });
    }

    public function test_invoke_creates_unprocessed_notification_for_daily_preference(): void
    {
        Mail::fake();

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

        $notification = EventNotification::where(EventNotification::TROOPER_ID, $trooper->id)->first();
        $this->assertNull($notification->{EventNotification::PROCESSED_AT});
    }

    public function test_invoke_does_not_send_email_for_daily_preference(): void
    {
        Mail::fake();

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

        Mail::assertNothingQueued();
    }

    public function test_invoke_returns_null_when_email_invalid(): void
    {
        Mail::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create([
            Trooper::EMAIL => '',
        ]);

        $command = new SendEventCreatedNotificationCommand(
            event: $event,
            trooper: $trooper
        );
        $handler = app(SendEventCreatedNotificationCommandHandler::class);

        $result = $handler($command);

        $this->assertNull($result);
        $this->assertDatabaseMissing('tt_event_notifications', [
            EventNotification::TROOPER_ID => $trooper->id,
        ]);
    }
}
