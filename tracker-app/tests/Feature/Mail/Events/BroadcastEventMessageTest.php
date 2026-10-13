<?php

declare(strict_types=1);

namespace Tests\Feature\Mail\Events;

use App\Mail\Events\BroadcastEventMessage;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastEventMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_envelope_contains_expected_subject(): void
    {
        config(['mail.prefix' => '[TEST]']);

        $event = Event::factory()->create();
        $mail = new BroadcastEventMessage($event, 'Please review the updated event details.');

        $this->assertSame('[TEST] 📣 Event Message', $mail->envelope()->subject);
    }

    public function test_content_contains_view_event_and_message(): void
    {
        $event = Event::factory()->create();
        $message = 'Please review the updated event details.';
        $mail = new BroadcastEventMessage($event, $message);

        $content = $mail->content();

        $this->assertSame('emails.events.broadcast-event-message', $content->view);
        $this->assertSame($event->id, $content->with['event']->id);
        $this->assertSame($message, $content->with['broadcast_message']);
        $this->assertSame([], $mail->attachments());
    }
}