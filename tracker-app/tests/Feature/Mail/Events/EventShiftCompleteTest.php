<?php

declare(strict_types=1);

namespace Tests\Feature\Mail\Events;

use App\Mail\Events\EventShiftComplete;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class EventShiftCompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_envelope_contains_expected_subject(): void
    {
        config(['mail.prefix' => '[TEST]']);

        $event_trooper = $this->createEventTrooper();
        $subject = new EventShiftComplete($event_trooper);

        $this->assertSame('[TEST] Event Shift Complete - Action Required', $subject->envelope()->subject);
    }

    public function test_content_contains_encrypted_statuses_and_relations(): void
    {
        $event_trooper = $this->createEventTrooper();

        $subject = new EventShiftComplete($event_trooper);
        $content = $subject->content();

        $this->assertSame('emails.events.event-shift-complete', $content->view);
        $this->assertSame($event_trooper->id, $content->with['event_trooper']->id);
        $this->assertSame($event_trooper->trooper->id, $content->with['trooper']->id);
        $this->assertSame($event_trooper->event_shift->event->id, $content->with['event']->id);
        $this->assertSame([], $subject->attachments());
    }

    private function createEventTrooper(): EventTrooper
    {
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->create();

        return EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create();
    }
}
