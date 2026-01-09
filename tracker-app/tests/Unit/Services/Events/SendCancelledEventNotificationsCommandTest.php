<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Events;

use App\Mail\Events\CancelledEventNotification;
use App\Models\Event;
use App\Models\Trooper;
use App\Services\Events\SendCancelledEventNotificationsCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Tests for the SendCancelledEventNotificationsCommand service.
 *
 * Validates that the service correctly sends cancellation emails
 * to troopers with valid email addresses.
 */
class SendCancelledEventNotificationsCommandTest extends TestCase
{
    use RefreshDatabase;

    private SendCancelledEventNotificationsCommand $subject;
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new SendCancelledEventNotificationsCommand();
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
        ($this->subject)($this->event, [$trooper]);

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
        ($this->subject)($this->event, [$trooper]);

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
        ($this->subject)($this->event, [$trooper]);

        // Assert
        Mail::assertNotQueued(CancelledEventNotification::class);
    }

    public function test_invoke_sends_emails_to_multiple_troopers(): void
    {
        // Arrange
        Mail::fake();
        $trooper1 = Trooper::factory()->create(['email' => 'trooper1@example.com']);
        $trooper2 = Trooper::factory()->create(['email' => 'trooper2@example.com']);
        $trooper3 = Trooper::factory()->create(['email' => 'trooper3@example.com']);

        // Act
        ($this->subject)($this->event, [$trooper1, $trooper2, $trooper3]);

        // Assert
        Mail::assertQueued(CancelledEventNotification::class, 3);
        Mail::assertQueued(CancelledEventNotification::class, fn($mail) => $mail->hasTo($trooper1->email));
        Mail::assertQueued(CancelledEventNotification::class, fn($mail) => $mail->hasTo($trooper2->email));
        Mail::assertQueued(CancelledEventNotification::class, fn($mail) => $mail->hasTo($trooper3->email));
    }

    public function test_invoke_sends_only_to_troopers_with_valid_emails(): void
    {
        // Arrange
        Mail::fake();
        $trooper_valid = Trooper::factory()->create(['email' => 'valid@example.com']);
        $trooper_invalid = Trooper::factory()->create(['email' => 'invalid-email']);
        $trooper_null = Trooper::factory()->create(['email' => null]);

        // Act
        ($this->subject)($this->event, [$trooper_valid, $trooper_invalid, $trooper_null]);

        // Assert
        Mail::assertQueued(CancelledEventNotification::class, 1);
        Mail::assertQueued(CancelledEventNotification::class, fn($mail) => $mail->hasTo($trooper_valid->email));
    }

    public function test_invoke_handles_empty_trooper_collection(): void
    {
        // Arrange
        Mail::fake();

        // Act
        ($this->subject)($this->event, []);

        // Assert
        Mail::assertNothingQueued();
    }

    public function test_invoke_queues_correct_mailable_with_event_data(): void
    {
        // Arrange
        Mail::fake();
        $trooper = Trooper::factory()->create(['email' => 'test@example.com']);

        // Act
        ($this->subject)($this->event, [$trooper]);

        // Assert
        Mail::assertQueued(CancelledEventNotification::class, function ($mail) use ($trooper)
        {
            return $mail->hasTo($trooper->email);
        });
    }
}
