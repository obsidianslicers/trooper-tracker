<?php

declare(strict_types=1);

namespace Tests\Unit\Mail\Events;

use App\Mail\Events\EventShiftComplete;
use App\Models\EventTrooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventShiftCompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_correct_subject(): void
    {
        $event_trooper = EventTrooper::factory()->create();

        $subject = new EventShiftComplete($event_trooper);

        $envelope = $subject->envelope();

        $this->assertEquals('Troop Tracker - Event Shift Complete - Action Required', $envelope->subject);
    }

    public function test_it_uses_correct_view(): void
    {
        $event_trooper = EventTrooper::factory()->create();

        $subject = new EventShiftComplete($event_trooper);

        $content = $subject->content();

        $this->assertEquals('emails.events.event-shift-complete', $content->view);
    }

    public function test_it_passes_required_data_to_view(): void
    {
        $event_trooper = EventTrooper::factory()->create();

        $subject = new EventShiftComplete($event_trooper);

        $content = $subject->content();

        $this->assertArrayHasKey('event_trooper', $content->with);
        $this->assertArrayHasKey('trooper', $content->with);
        $this->assertArrayHasKey('event_shift', $content->with);
        $this->assertArrayHasKey('event', $content->with);
        $this->assertArrayHasKey('able_status', $content->with);
        $this->assertArrayHasKey('unable_status', $content->with);
    }

    public function test_it_has_no_attachments(): void
    {
        $event_trooper = EventTrooper::factory()->create();

        $subject = new EventShiftComplete($event_trooper);

        $attachments = $subject->attachments();

        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }
}
