<?php

declare(strict_types=1);

namespace Tests\Feature\Mail\Events;

use App\Mail\Events\InstantEventNotification;
use App\Models\Event;
use App\Models\EventNotification;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class InstantEventNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_envelope_and_content_are_built_from_event_notification(): void
    {
        config(['mail.prefix' => '[TEST]']);

        $event = Event::factory()->create();
        EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->create();

        $event_notification = EventNotification::factory()
            ->forEvent($event)
            ->forTrooper($trooper)
            ->create();

        $mail = new InstantEventNotification($event_notification);
        $content = $mail->content();

        $this->assertSame('[TEST] New Event Posted', $mail->envelope()->subject);
        $this->assertSame('emails.events.instant-event-notification', $content->view);
        $this->assertSame($event_notification->id, $content->with['event_notification']->id);
        $this->assertSame($event->id, $content->with['event']->id);
        $this->assertSame([], $mail->attachments());
    }

    public function test_sent_sets_sent_at_timestamp(): void
    {
        $event_notification = EventNotification::factory()->create();

        $mail = new InstantEventNotification($event_notification);
        $mail->sent(new Email());

        $this->assertNotNull($event_notification->fresh()->sent_at);
    }
}
