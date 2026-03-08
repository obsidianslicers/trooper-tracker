<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Features\Events\Commands\SendEventCancelledNotificationCommand;
use App\Features\Events\Commands\SendEventCancelledNotificationCommandHandler;
use App\Mail\Events\CancelledEventNotification;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * @see SendEventCancelledNotificationCommandHandler
 */
class SendEventCancelledNotificationCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_queues_cancellation_email(): void
    {
        Mail::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create();

        $command = new SendEventCancelledNotificationCommand(
            event: $event,
            trooper: $trooper
        );
        $handler = app(SendEventCancelledNotificationCommandHandler::class);

        $handler($command);

        Mail::assertQueued(CancelledEventNotification::class, function ($mail) use ($trooper)
        {
            return $mail->hasTo($trooper->{Trooper::EMAIL});
        });
    }

    public function test_invoke_does_not_queue_email_when_email_invalid(): void
    {
        Mail::fake();

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

        Mail::assertNothingQueued();
    }

    public function test_invoke_returns_null(): void
    {
        Mail::fake();

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
