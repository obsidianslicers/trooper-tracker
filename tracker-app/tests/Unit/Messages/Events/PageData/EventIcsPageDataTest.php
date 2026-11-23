<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\PageData;

use App\Messages\Events\PageData\EventIcsPageData;
use App\Messages\Events\PageData\EventShiftIcsPageData;
use App\Models\Event;
use App\Models\EventShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventIcsPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_ics_is_named_after_the_event(): void
    {
        $event = Event::factory()->create(['name' => 'Library Visit']);

        $subject = new EventIcsPageData($event);
        $result = $subject->handle();

        $this->assertSame('library-visit.ics', $result['filename']);
        $this->assertStringContainsString('BEGIN:VCALENDAR', $result['content']);
        $this->assertStringContainsString('Library Visit', $result['content']);
    }

    public function test_shift_ics_is_named_after_the_event_and_shift(): void
    {
        $event = Event::factory()->create(['name' => 'Library Visit']);
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $result = (new EventShiftIcsPageData($event, $event_shift))->handle();

        $this->assertSame('library-visit-shift-'.$event_shift->id.'.ics', $result['filename']);
        $this->assertStringContainsString('BEGIN:VEVENT', $result['content']);
    }
}
