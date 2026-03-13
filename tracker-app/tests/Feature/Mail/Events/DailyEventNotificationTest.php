<?php

declare(strict_types=1);

namespace Tests\Feature\Mail\Events;

use App\Mail\Events\DailyEventNotification;
use App\Models\Event;
use App\Models\EventNotification;
use App\Models\EventShift;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class DailyEventNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_loads_relations_and_returns_digest_view(): void
    {
        config(['mail.prefix' => '[TEST]']);

        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        EventShift::factory()->forEvent($event)->create();

        $trooper = Trooper::factory()->create();
        $event_notification = EventNotification::factory()
            ->forEvent($event)
            ->forTrooper($trooper)
            ->create();

        $mail = new DailyEventNotification(new EloquentCollection([$event_notification]));
        $content = $mail->content();

        $this->assertSame('[TEST] New Event Posted', $mail->envelope()->subject);
        $this->assertSame('emails.events.daily-event-notification', $content->view);
        $this->assertCount(1, $content->with['event_notifications']);
        $this->assertSame([], $mail->attachments());
    }

    public function test_sent_marks_each_notification_as_sent(): void
    {
        $first = EventNotification::factory()->create();
        $second = EventNotification::factory()->create();

        $mail = new DailyEventNotification(new EloquentCollection([$first, $second]));
        $mail->sent(new Email());

        $this->assertNotNull($first->fresh()->sent_at);
        $this->assertNotNull($second->fresh()->sent_at);
    }
}
