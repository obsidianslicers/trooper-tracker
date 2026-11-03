<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Enums\NotificationFrequency;
use App\Messages\Events\Commands\SendEventCreatedNotification;
use App\Models\Event;
use App\Models\EventNotification;
use App\Models\Trooper;
use App\Notifications\Events\EventCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendEventCreatedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sends_notification_for_instant_preference(): void
    {
        Notification::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT,
        ]);

        $command = new SendEventCreatedNotification(
            event: $event,
            trooper: $trooper
        );

        $command->handle();

        Notification::assertSentTo($trooper, EventCreatedNotification::class);
    }

    public function test_handle_sends_notification_for_daily_preference(): void
    {
        Notification::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);

        $command = new SendEventCreatedNotification(
            event: $event,
            trooper: $trooper
        );

        $command->handle();

        Notification::assertSentTo($trooper, EventCreatedNotification::class);
    }

    public function test_handle_creates_unprocessed_event_notification_for_daily_preference(): void
    {
        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
            Trooper::EMAIL => 'valid@example.com',
        ]);

        $command = new SendEventCreatedNotification(
            event: $event,
            trooper: $trooper
        );

        $command->handle();

        $notification = EventNotification::where(EventNotification::TROOPER_ID, $trooper->id)->first();
        $this->assertNotNull($notification);
        $this->assertNull($notification->{EventNotification::PROCESSED_AT});
    }

    public function test_handle_creates_processed_event_notification_for_instant_preference(): void
    {
        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT,
            Trooper::EMAIL => 'valid@example.com',
        ]);

        $command = new SendEventCreatedNotification(
            event: $event,
            trooper: $trooper
        );

        $command->handle();

        $notification = EventNotification::where(EventNotification::TROOPER_ID, $trooper->id)->first();
        $this->assertNotNull($notification);
        $this->assertNotNull($notification->{EventNotification::PROCESSED_AT});
    }

    public function test_handle_sends_notification_even_when_email_invalid(): void
    {
        Notification::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create([
            Trooper::EMAIL => '',
        ]);

        $command = new SendEventCreatedNotification(
            event: $event,
            trooper: $trooper
        );

        $command->handle();

        Notification::assertSentTo($trooper, EventCreatedNotification::class);
    }
}
