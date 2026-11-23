<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\PageData;

use App\Messages\Events\PageData\RosterCsvPageData;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RosterCsvPageDataTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private EventShift $first_shift;

    private EventShift $second_shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->event = Event::factory()->create([
            'name' => 'Library Visit',
            'event_start' => Carbon::parse('2026-06-01 10:00:00'),
            'event_end' => Carbon::parse('2026-06-01 14:00:00'),
        ]);
        $this->first_shift = EventShift::factory()->forEvent($this->event)->create();
        $this->second_shift = EventShift::factory()->forEvent($this->event)->create();

        $trooper = Trooper::factory()->asActive()->create(['legal_name' => 'Jane Doe']);
        EventTrooper::factory()
            ->forEventShift($this->first_shift)
            ->forTrooper($trooper)
            ->withCostume(Costume::factory()->withName('Stormtrooper')->create())
            ->asGoing()
            ->create();
        EventGuest::factory()->forEventShift($this->second_shift)->withName('Uncle Owen')->asGoing()->create();
    }

    public function test_handle_lists_every_shift_with_the_event_name_above(): void
    {
        $subject = new RosterCsvPageData($this->event);
        $result = $subject->handle();

        $this->assertSame('event-roster-library-visit-all-shifts.csv', $result['filename']);
        $this->assertSame('Library Visit', $result['rows'][0][5]);
        $this->assertSame(array_fill(0, RosterCsvPageData::COLUMN_COUNT, ''), $result['rows'][1]);
        $this->assertSame(RosterCsvPageData::HEADINGS, $result['rows'][2]);
        $this->assertCount(5, $result['rows']);

        $trooper_row = $result['rows'][3];
        $this->assertSame(['2026-06-01', '10:00am - 2:00pm'], array_slice($trooper_row, 0, 2));
        $this->assertSame(['Jane Doe', '', 'Stormtrooper'], array_slice($trooper_row, 3, 3));
        $this->assertSame('Going', $trooper_row[8]);
        $this->assertSame('Uncle Owen', $result['rows'][4][4]);
    }

    public function test_handle_lists_only_the_chosen_shift(): void
    {
        $result = (new RosterCsvPageData($this->event, $this->second_shift))->handle();

        $this->assertSame('event-roster-library-visit-shift-'.$this->second_shift->id.'.csv', $result['filename']);
        $this->assertCount(4, $result['rows']);
        $this->assertSame('Uncle Owen', $result['rows'][3][4]);
    }
}
