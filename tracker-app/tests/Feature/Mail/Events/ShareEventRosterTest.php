<?php

declare(strict_types=1);

namespace Tests\Feature\Mail\Events;

use App\Mail\Events\ShareEventRoster;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareEventRosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_envelope_contains_expected_subject(): void
    {
        config(['mail.prefix' => '[TEST]']);

        $event_share = $this->createEventShare();
        $mail = new ShareEventRoster($event_share);

        $this->assertSame('[TEST] Event Roster Link', $mail->envelope()->subject);
    }

    public function test_content_contains_expected_view_and_relations(): void
    {
        $event_share = $this->createEventShare();

        $mail = new ShareEventRoster($event_share);
        $content = $mail->content();

        $this->assertSame('emails.events.share-event-roster', $content->view);
        $this->assertSame($event_share->id, $content->with['event_share']->id);
        $this->assertSame($event_share->trooper->id, $content->with['trooper']->id);
        $this->assertSame($event_share->event->id, $content->with['event']->id);
        $this->assertSame([], $mail->attachments());
    }

    private function createEventShare(): EventShare
    {
        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create();

        return EventShare::factory()
            ->forEvent($event)
            ->forTrooper($trooper)
            ->create();
    }
}
