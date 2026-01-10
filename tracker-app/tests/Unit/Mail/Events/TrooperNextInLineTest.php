<?php

declare(strict_types=1);

namespace Tests\Unit\Mail\Events;

use App\Mail\Events\TrooperNextInLine;
use App\Models\EventTrooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrooperNextInLineTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_correct_subject(): void
    {
        $event_trooper = EventTrooper::factory()->create();

        $subject = new TrooperNextInLine($event_trooper);

        $envelope = $subject->envelope();

        $this->assertEquals('Troop Tracker - Event Sign-Up Status Update - GOING', $envelope->subject);
    }

    public function test_it_uses_correct_view(): void
    {
        $event_trooper = EventTrooper::factory()->create();

        $subject = new TrooperNextInLine($event_trooper);

        $content = $subject->content();

        $this->assertEquals('emails.events.trooper-nextinline', $content->view);
    }

    public function test_it_passes_required_data_to_view(): void
    {
        $event_trooper = EventTrooper::factory()->create();

        $subject = new TrooperNextInLine($event_trooper);

        $content = $subject->content();

        $this->assertArrayHasKey('event_trooper', $content->with);
        $this->assertArrayHasKey('trooper', $content->with);
        $this->assertArrayHasKey('event_shift', $content->with);
        $this->assertArrayHasKey('event', $content->with);
        $this->assertArrayHasKey('link', $content->with);
    }

    public function test_it_has_no_attachments(): void
    {
        $event_trooper = EventTrooper::factory()->create();

        $subject = new TrooperNextInLine($event_trooper);

        $attachments = $subject->attachments();

        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }
}
