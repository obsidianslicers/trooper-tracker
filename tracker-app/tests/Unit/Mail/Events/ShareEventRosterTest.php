<?php

declare(strict_types=1);

namespace Tests\Unit\Mail\Events;

use App\Mail\Events\ShareEventRoster;
use App\Models\EventShare;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareEventRosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_correct_subject(): void
    {
        $event_share = EventShare::factory()->create();

        $subject = new ShareEventRoster($event_share);

        $envelope = $subject->envelope();

        $this->assertEquals('[Troop Tracker] Event Roster Link', $envelope->subject);
    }

    public function test_it_uses_correct_view(): void
    {
        $event_share = EventShare::factory()->create();

        $subject = new ShareEventRoster($event_share);

        $content = $subject->content();

        $this->assertEquals('emails.events.share-event-roster', $content->view);
    }

    public function test_it_passes_required_data_to_view(): void
    {
        $event_share = EventShare::factory()->create();

        $subject = new ShareEventRoster($event_share);

        $content = $subject->content();

        $this->assertArrayHasKey('event_share', $content->with);
        $this->assertArrayHasKey('trooper', $content->with);
        $this->assertArrayHasKey('event', $content->with);
    }

    public function test_it_has_no_attachments(): void
    {
        $event_share = EventShare::factory()->create();

        $subject = new ShareEventRoster($event_share);

        $attachments = $subject->attachments();

        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }
}
