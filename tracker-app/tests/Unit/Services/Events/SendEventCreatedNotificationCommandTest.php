<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Events;

use App\Enums\NotificationFrequency;
use App\Mail\Events\InstantEventNotification;
use App\Models\Event;
use App\Models\EventNotification;
use App\Models\Trooper;
use App\Services\Events\SendEventCreatedNotificationCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Tests for the SendEventCreatedNotificationCommand service.
 *
 * Validates that the service correctly creates event notifications
 * and sends instant emails based on trooper preferences.
 */
class SendEventCreatedNotificationCommandTest extends TestCase
{
    use RefreshDatabase;

    private SendEventCreatedNotificationCommand $subject;
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new SendEventCreatedNotificationCommand();
        $this->event = Event::factory()->create();
    }

    public function test_invoke_creates_notification_for_trooper_with_valid_email(): void
    {
        // Arrange
        Mail::fake();
        $trooper = Trooper::factory()->create([
            'email' => 'valid@example.com',
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);

        // Act
        ($this->subject)($this->event, $trooper);

        // Assert
        $this->assertDatabaseHas('tt_event_notifications', [
            'event_id' => $this->event->id,
            'trooper_id' => $trooper->id,
        ]);
    }

    public function test_invoke_does_not_create_notification_for_trooper_with_invalid_email(): void
    {
        // Arrange
        Mail::fake();
        $trooper = Trooper::factory()->create([
            'email' => 'invalid-email',
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);

        // Act
        ($this->subject)($this->event, $trooper);

        // Assert
        $this->assertDatabaseMissing('tt_event_notifications', [
            'event_id' => $this->event->id,
            'trooper_id' => $trooper->id,
        ]);
    }

    public function test_invoke_sends_instant_email_for_instant_notification_preference(): void
    {
        // Arrange
        Mail::fake();
        $trooper = Trooper::factory()->create([
            'email' => 'instant@example.com',
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);

        // Act
        ($this->subject)($this->event, $trooper);

        // Assert
        Mail::assertQueued(InstantEventNotification::class, function ($mail) use ($trooper)
        {
            return $mail->hasTo($trooper->email);
        });
    }

    public function test_invoke_does_not_send_email_for_daily_notification_preference(): void
    {
        // Arrange
        Mail::fake();
        $trooper = Trooper::factory()->create([
            'email' => 'daily@example.com',
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);

        // Act
        ($this->subject)($this->event, $trooper);

        // Assert
        Mail::assertNotQueued(InstantEventNotification::class);
    }

    public function test_invoke_marks_notification_as_processed_for_instant_preference(): void
    {
        // Arrange
        Mail::fake();
        $trooper = Trooper::factory()->create([
            'email' => 'instant@example.com',
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);

        // Act
        ($this->subject)($this->event, $trooper);

        // Assert
        $notification = EventNotification::where('trooper_id', $trooper->id)
            ->where('event_id', $this->event->id)
            ->first();

        $this->assertNotNull($notification);
        $this->assertNotNull($notification->processed_at);
    }

    public function test_invoke_does_not_mark_notification_as_processed_for_daily_preference(): void
    {
        // Arrange
        Mail::fake();
        $trooper = Trooper::factory()->create([
            'email' => 'daily@example.com',
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);

        // Act
        ($this->subject)($this->event, $trooper);

        // Assert
        $notification = EventNotification::where('trooper_id', $trooper->id)
            ->where('event_id', $this->event->id)
            ->first();

        $this->assertNotNull($notification);
        $this->assertNull($notification->processed_at);
    }

    public function test_invoke_handles_multiple_troopers(): void
    {
        // Arrange
        Mail::fake();
        $trooper1 = Trooper::factory()->create([
            'email' => 'trooper1@example.com',
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);
        $trooper2 = Trooper::factory()->create([
            'email' => 'trooper2@example.com',
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);
        $trooper3 = Trooper::factory()->create([
            'email' => 'trooper3@example.com',
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);

        // Act
        ($this->subject)($this->event, $trooper1);
        ($this->subject)($this->event, $trooper2);
        ($this->subject)($this->event, $trooper3);

        // Assert
        $this->assertDatabaseHas('tt_event_notifications', [
            'event_id' => $this->event->id,
            'trooper_id' => $trooper1->id,
        ]);
        $this->assertDatabaseHas('tt_event_notifications', [
            'event_id' => $this->event->id,
            'trooper_id' => $trooper2->id,
        ]);
        $this->assertDatabaseHas('tt_event_notifications', [
            'event_id' => $this->event->id,
            'trooper_id' => $trooper3->id,
        ]);

        Mail::assertQueued(InstantEventNotification::class, 2);
    }

    public function test_invoke_handles_mixed_valid_and_invalid_emails(): void
    {
        // Arrange
        Mail::fake();
        $trooper_valid = Trooper::factory()->create([
            'email' => 'valid@example.com',
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);
        $trooper_invalid = Trooper::factory()->create([
            'email' => 'invalid-email',
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);

        // Act
        ($this->subject)($this->event, $trooper_valid);
        ($this->subject)($this->event, $trooper_invalid);

        // Assert
        $this->assertDatabaseHas('tt_event_notifications', [
            'event_id' => $this->event->id,
            'trooper_id' => $trooper_valid->id,
        ]);
        $this->assertDatabaseMissing('tt_event_notifications', [
            'event_id' => $this->event->id,
            'trooper_id' => $trooper_invalid->id,
        ]);

        Mail::assertQueued(InstantEventNotification::class, 1);
    }

    public function test_invoke_does_not_send_email_for_never_notification_preference(): void
    {
        // Arrange
        Mail::fake();
        $trooper = Trooper::factory()->create([
            'email' => 'never@example.com',
            'notification_frequency' => NotificationFrequency::NEVER,
        ]);

        // Act
        ($this->subject)($this->event, $trooper);

        // Assert
        Mail::assertNotQueued(InstantEventNotification::class);
    }

    public function test_invoke_creates_unprocessed_notification_for_never_preference(): void
    {
        // Arrange
        Mail::fake();
        $trooper = Trooper::factory()->create([
            'email' => 'never@example.com',
            'notification_frequency' => NotificationFrequency::NEVER,
        ]);

        // Act
        ($this->subject)($this->event, $trooper);

        // Assert
        $notification = EventNotification::where('trooper_id', $trooper->id)
            ->where('event_id', $this->event->id)
            ->first();

        $this->assertNotNull($notification);
        $this->assertNull($notification->processed_at);
    }
}
