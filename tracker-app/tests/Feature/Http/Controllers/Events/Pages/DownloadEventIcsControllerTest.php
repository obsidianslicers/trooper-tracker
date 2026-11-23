<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events\Pages;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DownloadEventIcsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_downloads_the_event_as_a_calendar_file(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create(['name' => 'Library Visit']);

        $response = $this->actingAs($trooper)->get(route('events.download-ics', $event));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        $response->assertDownload('library-visit.ics');
        $this->assertStringContainsString('BEGIN:VCALENDAR', $response->streamedContent());
    }

    public function test_invoke_downloads_one_shift_as_a_calendar_file(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create(['name' => 'Library Visit']);
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $response = $this->actingAs($trooper)->get(route('events.download-shift-ics', [$event, $event_shift]));

        $response->assertOk();
        $response->assertDownload('library-visit-shift-'.$event_shift->id.'.ics');
    }

    public function test_invoke_refuses_a_shift_of_another_event(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent(Event::factory()->create())->create();

        $response = $this->actingAs($trooper)->get(route('events.download-shift-ics', [$event, $event_shift]));

        $response->assertNotFound();
    }
}
