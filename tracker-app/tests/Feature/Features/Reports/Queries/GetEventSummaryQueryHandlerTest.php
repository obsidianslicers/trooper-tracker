<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Features\Reports\Queries\GetEventSummaryQuery;
use App\Features\Reports\Queries\GetEventSummaryQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetEventSummaryQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_closed_events_for_moderator(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $event = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(10))->create();
        Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create(); // Other org

        $subject = new GetEventSummaryQueryHandler();

        $result = $subject(new GetEventSummaryQuery($moderator, 30));

        $this->assertCount(1, $result);
        $this->assertSame($event->id, $result->first()->id);
    }

    public function test_invoke_adds_event_shifts_count_property(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $event = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(10))->create();
        EventShift::factory()->forEvent($event)->count(3)->create();

        $subject = new GetEventSummaryQueryHandler();

        $result = $subject(new GetEventSummaryQuery($moderator, 30));

        $this->assertTrue(isset($result->first()->event_shifts_count));
        $this->assertSame(3, $result->first()->event_shifts_count);
    }

    public function test_invoke_adds_total_trooper_count_property(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $event = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(10))->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift)->asAttended()->count(5)->create();

        $subject = new GetEventSummaryQueryHandler();

        $result = $subject(new GetEventSummaryQuery($moderator, 30));

        $this->assertTrue(isset($result->first()->total_trooper_count));
        $this->assertSame(5, $result->first()->total_trooper_count);
    }

    public function test_invoke_adds_unique_trooper_count_property(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $trooper = Trooper::factory()->asMember()->create();

        $event = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(10))->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->asAttended()->create();

        $subject = new GetEventSummaryQueryHandler();

        $result = $subject(new GetEventSummaryQuery($moderator, 30));

        $this->assertTrue(isset($result->first()->unique_trooper_count));
        $this->assertSame(1, $result->first()->unique_trooper_count);
    }

    public function test_invoke_respects_lookback_period(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-02-15'))->create();
        Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-01-15'))->create();

        $subject = new GetEventSummaryQueryHandler();

        $result = $subject(new GetEventSummaryQuery($moderator, Carbon::parse('2026-02-01')));

        $this->assertCount(1, $result);
    }

    public function test_invoke_respects_show_all_parameter(): void
    {
        Trooper::factory()->asModerator()->create();

        Event::factory()->asClosed()->withEventStart(now()->subDays(10))->count(3)->create();

        $subject = new GetEventSummaryQueryHandler();

        $result = $subject(new GetEventSummaryQuery(new Trooper(), 30, true));

        $this->assertCount(3, $result);
    }
}
