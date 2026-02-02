<?php

declare(strict_types=1);

namespace Tests\Unit\Mail\Events;

use App\Mail\Events\CancelledEventNotification;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelledEventNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_correct_subject(): void
    {
        $event = Event::factory()->create();

        $subject = new CancelledEventNotification($event);

        $envelope = $subject->envelope();

        $this->assertEquals('[Troop Tracker] Event Cancelled', $envelope->subject);
    }

    public function test_it_uses_correct_view(): void
    {
        $event = Event::factory()->create();

        $subject = new CancelledEventNotification($event);

        $content = $subject->content();

        $this->assertEquals('emails.events.cancelled-event-notification', $content->view);
    }

    public function test_it_passes_event_to_view(): void
    {
        $event = Event::factory()->create();

        $subject = new CancelledEventNotification($event);

        $content = $subject->content();

        $this->assertArrayHasKey('event', $content->with);
        $this->assertSame($event, $content->with['event']);
    }

    public function test_it_has_no_attachments(): void
    {
        $event = Event::factory()->create();

        $subject = new CancelledEventNotification($event);

        $attachments = $subject->attachments();

        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }
}
