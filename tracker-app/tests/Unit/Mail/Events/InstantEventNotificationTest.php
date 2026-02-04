<?php

declare(strict_types=1);

namespace Tests\Unit\Mail\Events;

use App\Mail\Events\InstantEventNotification;
use App\Models\EventNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstantEventNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_correct_subject(): void
    {
        $event_notification = EventNotification::factory()->create();

        $subject = new InstantEventNotification($event_notification);

        $envelope = $subject->envelope();

        $this->assertEquals('[Troop Tracker] New Event Posted', $envelope->subject);
    }

    public function test_it_uses_correct_view(): void
    {
        $event_notification = EventNotification::factory()->create();

        $subject = new InstantEventNotification($event_notification);

        $content = $subject->content();

        $this->assertEquals('emails.events.instant-event-notification', $content->view);
    }

    public function test_it_passes_required_data_to_view(): void
    {
        $event_notification = EventNotification::factory()->create();

        $subject = new InstantEventNotification($event_notification);

        $content = $subject->content();

        $this->assertArrayHasKey('event_notification', $content->with);
        $this->assertArrayHasKey('event_shifts', $content->with);
        $this->assertArrayHasKey('event', $content->with);
    }

    public function test_it_has_no_attachments(): void
    {
        $event_notification = EventNotification::factory()->create();

        $subject = new InstantEventNotification($event_notification);

        $attachments = $subject->attachments();

        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }

    public function test_sent_callback_updates_sent_at_timestamp(): void
    {
        $event_notification = EventNotification::factory()->create([
            'sent_at' => null,
        ]);

        $subject = new InstantEventNotification($event_notification);

        $subject->sent(null);

        $this->assertNotNull($event_notification->fresh()->sent_at);
    }
}
