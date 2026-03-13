<?php

declare(strict_types=1);

namespace Tests\Feature\Mail\Events;

use App\Mail\Events\CancelledEventNotification;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelledEventNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_envelope_contains_expected_subject(): void
    {
        config(['mail.prefix' => '[TEST]']);

        $event = Event::factory()->create();
        $mail = new CancelledEventNotification($event);

        $this->assertSame('[TEST] Event Cancelled', $mail->envelope()->subject);
    }

    public function test_content_contains_view_and_event(): void
    {
        $event = Event::factory()->create();

        $mail = new CancelledEventNotification($event);
        $content = $mail->content();

        $this->assertSame('emails.events.cancelled-event-notification', $content->view);
        $this->assertSame($event->id, $content->with['event']->id);
        $this->assertSame([], $mail->attachments());
    }
}
