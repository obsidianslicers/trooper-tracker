<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events\Pages;

use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DownloadRosterCsvControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_shifts_downloads_the_roster(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->withVerifiedEmail()->create();
        $event = Event::factory()->create(['name' => 'Library Visit']);
        $event_shift = EventShift::factory()->forEvent($event)->create();
        EventGuest::factory()->forEventShift($event_shift)->withName('Uncle Owen')->asGoing()->create();

        $response = $this->actingAs($admin)->get(route('events.download-roster-csv', $event));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertDownload('event-roster-library-visit-all-shifts.csv');
        $content = $response->streamedContent();
        $this->assertStringContainsString('"Event Date","Event Time"', $content);
        $this->assertStringContainsString('"Uncle Owen"', $content);
    }

    public function test_single_shift_downloads_that_shift(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->withVerifiedEmail()->create();
        $event = Event::factory()->create(['name' => 'Library Visit']);
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $response = $this->actingAs($admin)->get(route('events.download-shift-roster-csv', [$event, $event_shift]));

        $response->assertOk();
        $response->assertDownload('event-roster-library-visit-shift-'.$event_shift->id.'.csv');
    }

    public function test_single_shift_refuses_a_shift_of_another_event(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->withVerifiedEmail()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent(Event::factory()->create())->create();

        $response = $this->actingAs($admin)->get(route('events.download-shift-roster-csv', [$event, $event_shift]));

        $response->assertNotFound();
    }

    public function test_all_shifts_is_only_for_those_who_can_update_the_event(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($trooper)->get(route('events.download-roster-csv', $event));

        $response->assertForbidden();
    }
}
