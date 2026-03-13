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

        $result = $subject(new GetTrooperEventSummaryQuery($moderator, 30));

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

        $result = $subject(new GetTrooperEventSummaryQuery($moderator, 30));

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

        $result = $subject(new GetTrooperEventSummaryQuery($moderator, 30));

        $this->assertTrue(isset($result->first()->events_count));
        $this->assertSame(2, $result->first()->events_count);
    }

    public function test_invoke_adds_attended_event_ids_property(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $trooper = Trooper::factory()->asMember()->create();

        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(10))->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();

        $result = $subject(new GetTrooperEventSummaryQuery($moderator, 30));

        $this->assertTrue(isset($result->first()->attended_event_ids));
        $this->assertContains($event->id, $result->first()->attended_event_ids->toArray());
    }

    public function test_invoke_respects_lookback_period(): void
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

        $result = $subject(new GetTrooperEventSummaryQuery($moderator, Carbon::parse('2026-02-01')));

        $this->assertSame(1, $result->first()->events_count);
    }
}
