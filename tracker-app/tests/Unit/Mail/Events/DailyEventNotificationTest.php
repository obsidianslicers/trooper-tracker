<?php

declare(strict_types=1);

namespace Tests\Unit\Mail\Events;

use App\Mail\Events\DailyEventNotification;
use App\Models\EventNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyEventNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_correct_subject(): void
    {
        $event_notifications = EventNotification::factory()->count(0)->make();

        $subject = new DailyEventNotification($event_notifications);

        $envelope = $subject->envelope();

        $this->assertEquals('Troop Tracker - New Event Posted', $envelope->subject);
    }

    public function test_it_uses_correct_view(): void
    {
        $event_notifications = EventNotification::factory()->count(0)->make();

        $subject = new DailyEventNotification($event_notifications);

        $content = $subject->content();

        $this->assertEquals('emails.events.daily-event-notification', $content->view);
    }

    public function test_it_passes_event_notifications_to_view(): void
    {
        $event_notifications = EventNotification::factory()->count(1)->create();

        $subject = new DailyEventNotification($event_notifications);

        $content = $subject->content();

        $this->assertArrayHasKey('event_notifications', $content->with);
        $this->assertCount(1, $content->with['event_notifications']);
    }

    public function test_it_has_no_attachments(): void
    {
        $event_notifications = EventNotification::factory()->count(0)->make();

        $subject = new DailyEventNotification($event_notifications);

        $attachments = $subject->attachments();

        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }

    public function test_sent_callback_updates_all_sent_at_timestamps(): void
    {
        $event_notifications = EventNotification::factory()->count(2)->create([
            'sent_at' => null,
        ]);

        $subject = new DailyEventNotification($event_notifications);

        $subject->sent(null);

        foreach ($event_notifications as $notification)
        {
            $this->assertNotNull($notification->fresh()->sent_at);
        }
    }
}
