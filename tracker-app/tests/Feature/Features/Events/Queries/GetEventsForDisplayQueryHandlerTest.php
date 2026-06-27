<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Features\Events\Queries\GetEventsForDisplayQuery;
use App\Features\Events\Queries\GetEventsForDisplayQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetEventsForDisplayQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_invoke_returns_only_upcoming_open_events(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));

        $upcoming_open = Event::factory()
            ->withEventStart(now()->addDays(3))
            ->withEventEnd(now()->addDays(3)->addHours(4))
            ->create();
        Event::factory()
            ->asClosed()
            ->withEventStart(now()->addDays(3))
            ->withEventEnd(now()->addDays(3)->addHours(4))
            ->create();
        Event::factory()
            ->withEventStart(Carbon::parse('2020-01-01 00:00:00'))
            ->withEventEnd(Carbon::parse('2020-01-01 04:00:00'))
            ->create();

        $subject = new GetEventsForDisplayQueryHandler();

        $result = $subject(new GetEventsForDisplayQuery());

        $this->assertCount(1, $result);
        $this->assertSame($upcoming_open->id, $result->first()->id);
    }

    public function test_invoke_keeps_multi_day_event_visible_until_latest_shift_ends(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-02 12:00:00'));

        $event = Event::factory()
            ->withEventStart(Carbon::parse('2026-06-01 10:00:00'))
            ->withEventEnd(Carbon::parse('2026-06-01 12:00:00'))
            ->create();

        EventShift::factory()
            ->forEvent($event)
            ->withShiftStartsAt(Carbon::parse('2026-06-01 10:00:00'))
            ->withShiftEndsAt(Carbon::parse('2026-06-01 12:00:00'))
            ->create();
        EventShift::factory()
            ->forEvent($event)
            ->withShiftStartsAt(Carbon::parse('2026-06-03 10:00:00'))
            ->withShiftEndsAt(Carbon::parse('2026-06-03 12:00:00'))
            ->create();

        $subject = new GetEventsForDisplayQueryHandler();

        $result = $subject(new GetEventsForDisplayQuery());

        $this->assertTrue($result->contains('id', $event->id));
    }

    public function test_invoke_hides_event_when_all_shifts_ended_before_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-02 12:00:00'));

        $event = Event::factory()
            ->withEventStart(Carbon::parse('2026-06-01 10:00:00'))
            ->withEventEnd(Carbon::parse('2026-06-03 12:00:00'))
            ->create();

        EventShift::factory()
            ->forEvent($event)
            ->withShiftStartsAt(Carbon::parse('2026-06-01 10:00:00'))
            ->withShiftEndsAt(Carbon::parse('2026-06-01 12:00:00'))
            ->create();

        $subject = new GetEventsForDisplayQueryHandler();

        $result = $subject(new GetEventsForDisplayQuery());

        $this->assertFalse($result->contains('id', $event->id));
    }
}
