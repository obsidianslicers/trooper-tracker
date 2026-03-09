<?php

declare(strict_types=1);

namespace Tests\Feature\Mail\Events;

use App\Mail\Events\TrooperSignUp;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrooperSignUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_envelope_contains_expected_subject(): void
    {
        config(['mail.prefix' => '[TEST]']);

        $event_trooper = $this->createEventTrooper();
        $mail = new TrooperSignUp($event_trooper);

        $this->assertSame('[TEST] Event Sign-Up Confirmation', $mail->envelope()->subject);
    }

    public function test_content_contains_expected_view_and_relations(): void
    {
        $event_trooper = $this->createEventTrooper();

        $mail = new TrooperSignUp($event_trooper);
        $content = $mail->content();

        $this->assertSame('emails.events.trooper-signup', $content->view);
        $this->assertSame($event_trooper->trooper->id, $content->with['trooper']->id);
        $this->assertSame($event_trooper->event_shift->id, $content->with['event_shift']->id);
        $this->assertSame($event_trooper->event_shift->event->id, $content->with['event']->id);
        $this->assertNotNull($content->with['link']);
        $this->assertSame([], $mail->attachments());
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
