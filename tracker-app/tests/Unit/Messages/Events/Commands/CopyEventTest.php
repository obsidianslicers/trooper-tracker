<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Enums\EventStatus;
use App\Messages\Events\Commands\CopyEvent;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CopyEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_copies_the_event_as_a_draft_moved_to_the_new_start(): void
    {
        $event = Event::factory()->create([
            Event::EVENT_START => Carbon::parse('2030-01-10 10:00'),
            Event::EVENT_END => Carbon::parse('2030-01-10 14:00'),
            Event::STATUS => EventStatus::OPEN,
        ]);
        $shift = EventShift::factory()->forEvent($event)
            ->withShiftStartsAt(Carbon::parse('2030-01-10 11:00'))
            ->withShiftEndsAt(Carbon::parse('2030-01-10 13:00'))
            ->create();
        EventShiftStation::factory()->forEventShift($shift)->withName('Gate')->create();
        EventOrganization::factory()->forEvent($event)->forOrganization(Organization::factory()->create())->create();

        $subject = new CopyEvent($event, 'Next Year', Carbon::parse('2031-01-10 10:00'));
        $copy = $subject->handle();

        $this->assertNotSame($event->id, $copy->id);
        $this->assertSame('Next Year', $copy->name);
        $this->assertSame(EventStatus::DRAFT, $copy->status);
        $this->assertSame('2031-01-10 14:00', $copy->event_end->format('Y-m-d H:i'));

        $copied_shift = $copy->event_shifts()->firstOrFail();
        $this->assertSame('2031-01-10 11:00', $copied_shift->shift_starts_at->format('Y-m-d H:i'));
        $this->assertSame(['Gate'], $copied_shift->event_shift_stations()->pluck(EventShiftStation::NAME)->all());
        $this->assertSame(1, $copy->event_organizations()->count());

        $this->assertSame('2030-01-10 10:00', $event->fresh()->event_start->format('Y-m-d H:i'));
    }
}
