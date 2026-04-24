<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Features\Reports\Queries\GetCostumeEventSummaryQuery;
use App\Features\Reports\Queries\GetCostumeEventSummaryQueryHandler;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetCostumeEventSummaryQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_costumes_with_attended_closed_events(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asMember()->create();

        $used_costume = Costume::factory()->withName('Used Costume')->create();
        Costume::factory()->withName('Unused Costume')->create();

        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(10))->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->withCostume($used_costume)
            ->asAttended()
            ->create();

        $subject = new GetCostumeEventSummaryQueryHandler();
        $result = $subject(new GetCostumeEventSummaryQuery($moderator));

        $this->assertCount(1, $result);
        $this->assertSame($used_costume->id, $result->first()->id);
    }

    public function test_invoke_excludes_non_attended_statuses(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $costume = Costume::factory()->create();

        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(10))->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->withCostume($costume)
            ->asGoing()
            ->create();

        $subject = new GetCostumeEventSummaryQueryHandler();
        $result = $subject(new GetCostumeEventSummaryQuery($moderator));

        $this->assertCount(0, $result);
    }

    public function test_invoke_excludes_attended_shifts_on_non_closed_events(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $costume = Costume::factory()->create();

        $event = Event::factory()->withEventStart(now()->subDays(10))->create(); // open by default
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->withCostume($costume)
            ->asAttended()
            ->create();

        $subject = new GetCostumeEventSummaryQueryHandler();
        $result = $subject(new GetCostumeEventSummaryQuery($moderator));

        $this->assertCount(0, $result);
    }

    public function test_invoke_adds_uses_count_and_events_count_properties(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $costume = Costume::factory()->withName('Deathtrooper')->create();

        $event1 = Event::factory()->asClosed()->withEventStart(now()->subDays(10))->create();
        $event2 = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();

        $shift1 = EventShift::factory()->forEvent($event1)->create();
        $shift2 = EventShift::factory()->forEvent($event1)->create();
        $shift3 = EventShift::factory()->forEvent($event2)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->withCostume($costume)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->withCostume($costume)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift3)->forTrooper($trooper)->withCostume($costume)->asAttended()->create();

        $subject = new GetCostumeEventSummaryQueryHandler();
        $result = $subject(new GetCostumeEventSummaryQuery($moderator));

        $this->assertSame(3, $result->first()->uses_count);
        $this->assertSame(2, $result->first()->events_count);
    }

    public function test_invoke_respects_date_start_and_date_end(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $costume = Costume::factory()->create();

        $event_before = Event::factory()->asClosed()->withEventStart(Carbon::parse('2026-01-10'))->create();
        $event_inside = Event::factory()->asClosed()->withEventStart(Carbon::parse('2026-02-15'))->create();
        $event_after = Event::factory()->asClosed()->withEventStart(Carbon::parse('2026-04-10'))->create();

        $shift_before = EventShift::factory()->forEvent($event_before)->create();
        $shift_inside = EventShift::factory()->forEvent($event_inside)->create();
        $shift_after = EventShift::factory()->forEvent($event_after)->create();

        EventTrooper::factory()->forEventShift($shift_before)->forTrooper($trooper)->withCostume($costume)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift_inside)->forTrooper($trooper)->withCostume($costume)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift_after)->forTrooper($trooper)->withCostume($costume)->asAttended()->create();

        $subject = new GetCostumeEventSummaryQueryHandler();
        $result = $subject(new GetCostumeEventSummaryQuery(
            $moderator,
            date_start: Carbon::parse('2026-02-01'),
            date_end: Carbon::parse('2026-03-01'),
        ));

        $this->assertCount(1, $result);
        $this->assertSame(1, $result->first()->uses_count);
        $this->assertSame(1, $result->first()->events_count);
    }

    public function test_invoke_organization_filter_counts_only_matching_org_shifts(): void
    {
        $this->skipIfSqlite();

        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $costume = Costume::factory()->create();
        $org = Organization::factory()->create();

        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->withCostume($costume)->withCostumeOrganizationIds([$org->id])->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->withCostume($costume)->withCostumeOrganizationIds([999])->asAttended()->create();

        $subject = new GetCostumeEventSummaryQueryHandler();
        $result = $subject(new GetCostumeEventSummaryQuery($moderator, organization: $org));

        $this->assertCount(1, $result);
        $this->assertSame(1, $result->first()->uses_count);
    }

    public function test_invoke_accessible_org_ids_filter_counts_only_accessible_org_shifts(): void
    {
        $this->skipIfSqlite();

        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $costume = Costume::factory()->create();
        $accessible_org = Organization::factory()->create();
        $other_org = Organization::factory()->create();

        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->withCostume($costume)->withCostumeOrganizationIds([$accessible_org->id])->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->withCostume($costume)->withCostumeOrganizationIds([$other_org->id])->asAttended()->create();

        $subject = new GetCostumeEventSummaryQueryHandler();
        $result = $subject(new GetCostumeEventSummaryQuery($moderator, accessible_org_ids: [$accessible_org->id]));

        $this->assertCount(1, $result);
        $this->assertSame(1, $result->first()->uses_count);
    }

    public function test_invoke_sorts_by_name_ascending(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asMember()->create();

        $zulu = Costume::factory()->withName('Zulu Costume')->create();
        $alpha = Costume::factory()->withName('Alpha Costume')->create();

        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->withCostume($zulu)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->withCostume($alpha)->asAttended()->create();

        $subject = new GetCostumeEventSummaryQueryHandler();
        $result = $subject(new GetCostumeEventSummaryQuery($moderator, sort: 'name', dir: 'asc'));

        $this->assertSame($alpha->id, $result->first()->id);
        $this->assertSame($zulu->id, $result->last()->id);
    }

    public function test_invoke_falls_back_to_default_sort_for_invalid_sort_or_dir(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asMember()->create();

        $few = Costume::factory()->withName('Few Uses')->create();
        $many = Costume::factory()->withName('Many Uses')->create();

        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();
        $shift3 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->withCostume($few)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->withCostume($many)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift3)->forTrooper($trooper)->withCostume($many)->asAttended()->create();

        $subject = new GetCostumeEventSummaryQueryHandler();
        $result = $subject(new GetCostumeEventSummaryQuery($moderator, sort: 'bad_column', dir: 'DROP TABLE'));

        $this->assertSame($many->id, $result->first()->id);
    }

    public function test_invoke_paginates_results(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();

        foreach (range(1, 5) as $i) {
            $trooper = Trooper::factory()->asMember()->create();
            $costume = Costume::factory()->withName(sprintf('Costume %02d', $i))->create();
            $shift = EventShift::factory()->forEvent($event)->create();

            EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->withCostume($costume)->asAttended()->create();
        }

        $subject = new GetCostumeEventSummaryQueryHandler();
        $result = $subject(new GetCostumeEventSummaryQuery($moderator, page_size: 2));

        $this->assertSame(2, $result->perPage());
        $this->assertSame(5, $result->total());
        $this->assertCount(2, $result->items());
    }
}
