<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Enums\EventStatus;
use App\Features\Events\Queries\GetEventsToCloseQuery;
use App\Features\Events\Queries\GetEventsToCloseQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetEventsToCloseQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_invoke_returns_active_events_with_end_in_the_past(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));

        $to_close = Event::factory()->withEventEnd(Carbon::parse('2026-03-01 00:00:00'))->create();
        Event::factory()->withEventEnd(now()->addDay())->create();
        Event::factory()->asClosed()->withEventEnd(Carbon::parse('2026-03-01 00:00:00'))->create();

        $subject = new GetEventsToCloseQueryHandler();

        $result = $subject(new GetEventsToCloseQuery());

        $this->assertCount(1, $result);
        $this->assertSame($to_close->id, $result->first()->id);
    }

    public function test_invoke_returns_active_events_when_all_shifts_ended_before_buffer(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));

        $to_close = Event::factory()
            ->withEventEnd(now()->addDay())
            ->create();

        EventShift::factory()
            ->forEvent($to_close)
            ->withShiftStartsAt(Carbon::parse('2026-05-30 10:00:00'))
            ->withShiftEndsAt(Carbon::parse('2026-05-30 12:00:00'))
            ->create();

        EventShift::factory()
            ->forEvent($to_close)
            ->withShiftStartsAt(Carbon::parse('2026-05-31 10:00:00'))
            ->withShiftEndsAt(Carbon::parse('2026-06-01 05:59:00'))
            ->create();

        $subject = new GetEventsToCloseQueryHandler();

        $result = $subject(new GetEventsToCloseQuery());

        $this->assertTrue($result->contains('id', $to_close->id));
    }

    public function test_invoke_does_not_return_event_when_any_shift_has_not_passed_buffer(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));

        $event = Event::factory()
            ->withEventEnd(Carbon::parse('2026-05-30 12:00:00'))
            ->create();

        EventShift::factory()
            ->forEvent($event)
            ->withShiftStartsAt(Carbon::parse('2026-05-30 10:00:00'))
            ->withShiftEndsAt(Carbon::parse('2026-05-30 12:00:00'))
            ->create();

        EventShift::factory()
            ->forEvent($event)
            ->withShiftStartsAt(Carbon::parse('2026-06-01 04:00:00'))
            ->withShiftEndsAt(Carbon::parse('2026-06-01 06:00:00'))
            ->create();

        $subject = new GetEventsToCloseQueryHandler();

        $result = $subject(new GetEventsToCloseQuery());

        $this->assertFalse($result->contains('id', $event->id));
    }

    public function test_invoke_uses_event_end_fallback_for_events_without_shifts(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));

        $to_close = Event::factory()
            ->withEventEnd(Carbon::parse('2026-06-01 05:59:00'))
            ->create();

        $subject = new GetEventsToCloseQueryHandler();

        $result = $subject(new GetEventsToCloseQuery());

        $this->assertTrue($result->contains('id', $to_close->id));
    }

    public function test_invoke_ignores_inactive_events_even_when_all_shifts_ended(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));

        $closed = Event::factory()
            ->asClosed()
            ->withEventEnd(Carbon::parse('2026-05-30 12:00:00'))
            ->create();
        $cancelled = Event::factory()
            ->withEventEnd(Carbon::parse('2026-05-30 12:00:00'))
            ->create([Event::STATUS => EventStatus::CANCELLED]);

        foreach ([$closed, $cancelled] as $event)
        {
            EventShift::factory()
                ->forEvent($event)
                ->withShiftStartsAt(Carbon::parse('2026-05-30 10:00:00'))
                ->withShiftEndsAt(Carbon::parse('2026-05-30 12:00:00'))
                ->create();
        }

        $subject = new GetEventsToCloseQueryHandler();

        $result = $subject(new GetEventsToCloseQuery());

        $this->assertFalse($result->contains('id', $closed->id));
        $this->assertFalse($result->contains('id', $cancelled->id));
    }
}
