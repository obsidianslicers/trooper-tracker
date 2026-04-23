<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Features\Reports\Queries\GetTrooperEventSummaryQuery;
use App\Features\Reports\Queries\GetTrooperEventSummaryQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperEventSummaryQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_troopers_with_attended_events(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $trooper_with_attendance = Trooper::factory()->asMember()->create();
        $trooper_without_attendance = Trooper::factory()->asMember()->create();

        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(10))->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper_with_attendance)
            ->asAttended()
            ->create();

        $subject = new GetTrooperEventSummaryQueryHandler();

        $result = $subject(new GetTrooperEventSummaryQuery($moderator));

        $this->assertCount(1, $result);
        $this->assertSame($trooper_with_attendance->id, $result->first()->id);
    }

    public function test_invoke_adds_event_shifts_count_property(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $trooper = Trooper::factory()->asMember()->create();

        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(10))->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();

        $result = $subject(new GetTrooperEventSummaryQuery($moderator));

        $this->assertTrue(isset($result->first()->event_shifts_count));
        $this->assertSame(2, $result->first()->event_shifts_count);
    }

    public function test_invoke_adds_events_count_property(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $trooper = Trooper::factory()->asMember()->create();

        $event1 = Event::factory()->asClosed()->withEventStart(now()->subDays(10))->create();
        $event2 = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();

        $shift1 = EventShift::factory()->forEvent($event1)->create();
        $shift2 = EventShift::factory()->forEvent($event2)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();

        $result = $subject(new GetTrooperEventSummaryQuery($moderator));

        $this->assertTrue(isset($result->first()->events_count));
        $this->assertSame(2, $result->first()->events_count);
    }

    public function test_invoke_respects_date_start(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $trooper = Trooper::factory()->asMember()->create();

        $event1 = Event::factory()->asClosed()->withEventStart(Carbon::parse('2026-02-15'))->create();
        $event2 = Event::factory()->asClosed()->withEventStart(Carbon::parse('2026-01-15'))->create();

        $shift1 = EventShift::factory()->forEvent($event1)->create();
        $shift2 = EventShift::factory()->forEvent($event2)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();

        $result = $subject(new GetTrooperEventSummaryQuery($moderator, date_start: Carbon::parse('2026-02-01')));

        $this->assertSame(1, $result->first()->events_count);
    }

    public function test_invoke_respects_date_end(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $trooper = Trooper::factory()->asMember()->create();

        $event1 = Event::factory()->asClosed()->withEventStart(Carbon::parse('2026-01-15'))->create();
        $event2 = Event::factory()->asClosed()->withEventStart(Carbon::parse('2026-03-15'))->create();

        $shift1 = EventShift::factory()->forEvent($event1)->create();
        $shift2 = EventShift::factory()->forEvent($event2)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();

        $result = $subject(new GetTrooperEventSummaryQuery($moderator, date_end: Carbon::parse('2026-02-01')));

        $this->assertSame(1, $result->first()->events_count);
    }

    public function test_invoke_filters_active_members_only(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $active_trooper = Trooper::factory()->asMember()->create();
        $inactive_trooper = Trooper::factory()->asRetired()->create();

        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($active_trooper)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($inactive_trooper)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();

        $result = $subject(new GetTrooperEventSummaryQuery($moderator, active_only: true));

        $this->assertCount(1, $result);
        $this->assertSame($active_trooper->id, $result->first()->id);
    }
}
