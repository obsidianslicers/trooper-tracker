<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Messages\Events\Commands\SendEventCancelledNotification;
use App\Models\Event;
use App\Models\Trooper;
use App\Notifications\Events\EventCancelledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendEventCancelledNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sends_cancellation_notification(): void
    {
        Notification::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create();

        $command = new SendEventCancelledNotification(
            event: $event,
            trooper: $trooper
        );

        $command->handle();

        Notification::assertSentTo($trooper, EventCancelledNotification::class);
    }

    public function test_handle_sends_notification_even_when_email_invalid(): void
    {
        Notification::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create([
            Trooper::EMAIL => '',
        ]);

        $command = new SendEventCancelledNotification(
            event: $event,
            trooper: $trooper
        );

        $command->handle();

        Notification::assertSentTo($trooper, EventCancelledNotification::class);
    }

    public function test_handle_returns_null(): void
    {
        Notification::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create();

        $command = new SendEventCancelledNotification(
            event: $event,
            trooper: $trooper
        );

        $result = $command->handle();

        $this->assertNull($result);
    }
}
