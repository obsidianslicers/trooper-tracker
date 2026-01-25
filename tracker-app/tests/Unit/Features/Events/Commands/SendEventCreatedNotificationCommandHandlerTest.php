<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Commands;

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

class SendEventCreatedNotificationCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_where_is_the_hang(): void
    {
        $notification = EventNotification::factory()->create();

        $start = microtime(true);
        // 1. Check Serialization
        serialize($notification);
        dump('Serialization took: ' . (microtime(true) - $start));

        // 2. Check View Rendering
        $start = microtime(true);
        view('emails.events.instant-event-notification', [
            'event_notification' => $notification,
            'event' => $notification->event,
            'event_shifts' => $notification->event->event_shifts ?? collect(),
        ])->render();
        dump('View Render took: ' . (microtime(true) - $start));

        // 3. Check Mail Fake Handshake
        $start = microtime(true);
        Mail::fake();
        Mail::to('test@example.com')->send(new InstantEventNotification($notification));
        dump('Mail Send took: ' . (microtime(true) - $start));
    }

    public function test_invoke_creates_notification_for_instant_trooper(): void
    {
        // Arrange
        Mail::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::EMAIL => 'test@example.com',
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT,
        ]);

        $command = new SendEventCreatedNotificationCommand($event, $trooper);
        $subject = new SendEventCreatedNotificationCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertDatabaseHas(EventNotification::class, [
            EventNotification::EVENT_ID => $event->id,
            EventNotification::TROOPER_ID => $trooper->id,
        ]);
        $this->assertNull($result);
    }

    public function test_invoke_marks_notification_as_processed_for_instant_trooper(): void
    {
        // Arrange
        Mail::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::EMAIL => 'test@example.com',
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT,
        ]);

        $command = new SendEventCreatedNotificationCommand($event, $trooper);
        $subject = new SendEventCreatedNotificationCommandHandler();

        // Act
        $subject($command);

        // Assert
        $notification = EventNotification::where(EventNotification::EVENT_ID, $event->id)
            ->where(EventNotification::TROOPER_ID, $trooper->id)
            ->first();

        $this->assertNotNull($notification->processed_at);
    }

    public function test_invoke_sends_instant_email_for_instant_trooper(): void
    {
        // Arrange
        Mail::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::EMAIL => 'test@example.com',
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT,
        ]);

        $command = new SendEventCreatedNotificationCommand($event, $trooper);
        $subject = new SendEventCreatedNotificationCommandHandler();

        // Act
        $subject($command);

        // Assert
        Mail::assertQueued(InstantEventNotification::class, function ($mail) use ($trooper)
        {
            return $mail->hasTo($trooper->email);
        });
    }

    public function test_invoke_creates_unprocessed_notification_for_daily_trooper(): void
    {
        // Arrange
        Mail::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::EMAIL => 'test@example.com',
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);

        $command = new SendEventCreatedNotificationCommand($event, $trooper);
        $subject = new SendEventCreatedNotificationCommandHandler();

        // Act
        $subject($command);

        // Assert
        $notification = EventNotification::where(EventNotification::EVENT_ID, $event->id)
            ->where(EventNotification::TROOPER_ID, $trooper->id)
            ->first();

        $this->assertNotNull($notification);
        $this->assertNull($notification->processed_at);
    }

    public function test_invoke_does_not_send_instant_email_for_daily_trooper(): void
    {
        // Arrange
        Mail::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::EMAIL => 'test@example.com',
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);

        $command = new SendEventCreatedNotificationCommand($event, $trooper);
        $subject = new SendEventCreatedNotificationCommandHandler();

        // Act
        $subject($command);

        // Assert
        Mail::assertNothingQueued();
    }

    public function test_invoke_does_nothing_when_trooper_has_invalid_email(): void
    {
        // Arrange
        Mail::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::EMAIL => '',
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT,
        ]);

        $command = new SendEventCreatedNotificationCommand($event, $trooper);
        $subject = new SendEventCreatedNotificationCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertDatabaseMissing(EventNotification::class, [
            EventNotification::EVENT_ID => $event->id,
            EventNotification::TROOPER_ID => $trooper->id,
        ]);
        Mail::assertNothingQueued();
        $this->assertNull($result);
    }

    public function test_invoke_returns_null(): void
    {
        // Arrange
        Mail::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::EMAIL => 'test@example.com',
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT,
        ]);

        $command = new SendEventCreatedNotificationCommand($event, $trooper);
        $subject = new SendEventCreatedNotificationCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertNull($result);
    }
}