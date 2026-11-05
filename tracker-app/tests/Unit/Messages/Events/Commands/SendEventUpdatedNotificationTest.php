<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Messages\Events\Commands\SendEventUpdatedNotification;
use App\Models\Event;
use App\Models\Trooper;
use App\Notifications\Events\EventUpdatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @see SendEventUpdatedNotification
 */
class SendEventUpdatedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_notifies_trooper_of_event_update(): void
    {
        Notification::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create();
        $changed_fields = [Event::NAME, Event::VENUE];

        (new SendEventUpdatedNotification($event, $trooper, $changed_fields))->handle();

        Notification::assertSentTo(
            $trooper,
            EventUpdatedNotification::class,
            fn (EventUpdatedNotification $notification) => $notification->toArray($trooper)['title'] === 'Event Updated: '.$event->name
        );
    }

    public function test_handle_respects_trooper_notification_preference_toggle(): void
    {
        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create([
            Trooper::NOTIFICATION_PREFERENCES => [
                'event_updated' => ['database' => false, 'mail' => false, 'fcm' => false],
            ],
        ]);

        $notification = new EventUpdatedNotification($event, [Event::NAME]);

        $this->assertSame([], $notification->via($trooper));
    }
}
