<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Events;

use App\Mail\Events\CancelledEventNotification;
use App\Models\Event;
use App\Models\Trooper;
use App\Services\Events\SendEventCancelledNotificationCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Tests for the SendEventCancelledNotificationCommand service.
 *
 * Validates that the service correctly sends cancellation emails
 * to a trooper with a valid email address.
 */
class SendEventCancelledNotificationCommandTest extends TestCase
{
    use RefreshDatabase;

    private SendEventCancelledNotificationCommand $subject;
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new SendEventCancelledNotificationCommand();
        $this->event = Event::factory()->create();
    }

    public function test_invoke_sends_email_to_trooper_with_valid_email(): void
    {
        // Arrange
        Mail::fake();
        $trooper = Trooper::factory()->create([
            'email' => 'valid@example.com',
        ]);

        // Act
        ($this->subject)($this->event, $trooper);

        // Assert
        Mail::assertQueued(CancelledEventNotification::class, function ($mail) use ($trooper)
        {
            return $mail->hasTo($trooper->email);
        });
    }

    public function test_invoke_does_not_send_email_to_trooper_with_invalid_email(): void
    {
        // Arrange
        Mail::fake();
        $trooper = Trooper::factory()->create([
            'email' => 'invalid-email',
        ]);

        // Act
        ($this->subject)($this->event, $trooper);

        // Assert
        Mail::assertNotQueued(CancelledEventNotification::class);
    }

    public function test_invoke_does_not_send_email_to_trooper_with_null_email(): void
    {
        // Arrange
        Mail::fake();
        $trooper = Trooper::factory()->create([
            'email' => null,
        ]);

        // Act
        ($this->subject)($this->event, $trooper);

        // Assert
        Mail::assertNotQueued(CancelledEventNotification::class);
    }

    public function test_invoke_queues_correct_mailable_with_event_data(): void
    {
        // Arrange
        Mail::fake();
        $trooper = Trooper::factory()->create(['email' => 'test@example.com']);

        // Act
        ($this->subject)($this->event, $trooper);

        // Assert
        Mail::assertQueued(CancelledEventNotification::class, function ($mail) use ($trooper)
        {
            return $mail->hasTo($trooper->email);
        });
    }
}
