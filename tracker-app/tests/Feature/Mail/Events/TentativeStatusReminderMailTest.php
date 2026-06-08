<?php

declare(strict_types=1);

namespace Tests\Feature\Mail\Events;

use App\Mail\Events\TentativeStatusReminderMail;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TentativeStatusReminderMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_envelope_has_correct_subject(): void
    {
        $event = Event::factory()->withEventStart(now()->addDays(3))->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->asMember()->create();
        $event_trooper = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asTentative()->create();

        $mail = new TentativeStatusReminderMail($event_trooper);

        $this->assertStringContainsString('Update Your Tentative Status', $mail->envelope()->subject);
    }

    public function test_content_returns_correct_view_and_data(): void
    {
        $event = Event::factory()->withEventStart(now()->addDays(3))->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->asMember()->create();
        $event_trooper = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asTentative()->create();

        $mail = new TentativeStatusReminderMail($event_trooper);
        $content = $mail->content();

        $this->assertSame('emails.events.tentative-status-reminder', $content->view);
        $this->assertArrayHasKey('event_trooper', $content->with);
        $this->assertArrayHasKey('event', $content->with);
        $this->assertArrayHasKey('days_until', $content->with);
    }
}
