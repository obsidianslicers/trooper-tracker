<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Commands;

use App\Features\Events\Commands\SendEventCancelledNotificationCommand;
use App\Features\Events\Commands\SendEventCancelledNotificationCommandHandler;
use App\Mail\Events\CancelledEventNotification;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendEventCancelledNotificationCommandHandlerTest extends TestCase
{
    use RefreshDatabase;
    public function test_invoke_sends_email_when_trooper_has_valid_email(): void
    {
        // Arrange
        Mail::fake();

        $event = Event::factory()->make();
        $trooper = Trooper::factory()->make([
            Trooper::EMAIL => 'valid@example.com',
        ]);

        $command = new SendEventCancelledNotificationCommand($event, $trooper);
        $subject = new SendEventCancelledNotificationCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        Mail::assertQueued(CancelledEventNotification::class, function ($mail) use ($trooper)
        {
            return $mail->hasTo($trooper->email);
        });
        $this->assertNull($result);
    }

    public function test_invoke_does_not_send_email_when_trooper_has_invalid_email(): void
    {
        // Arrange
        Mail::fake();

        $event = Event::factory()->make();
        $trooper = Trooper::factory()->make([
            Trooper::EMAIL => '',
        ]);

        $command = new SendEventCancelledNotificationCommand($event, $trooper);
        $subject = new SendEventCancelledNotificationCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        Mail::assertNothingQueued();
        $this->assertNull($result);
    }

    public function test_invoke_returns_null(): void
    {
        // Arrange
        Mail::fake();

        $event = Event::factory()->make();
        $trooper = Trooper::factory()->make([
            Trooper::EMAIL => 'test@example.com',
        ]);

        $command = new SendEventCancelledNotificationCommand($event, $trooper);
        $subject = new SendEventCancelledNotificationCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertNull($result);
    }
}