<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Features\Reports\Queries\GetTrooperEventSummaryQuery;
use App\Features\Reports\Queries\GetTrooperEventSummaryQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperEventSummaryQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Basic filtering
    // -------------------------------------------------------------------------

    public function test_invoke_returns_troopers_with_attended_closed_events(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();

        $trooper_with_attendance    = Trooper::factory()->asMember()->create();
        $trooper_without_attendance = Trooper::factory()->asMember()->create();

        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(10))->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper_with_attendance)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator));

        $this->assertCount(1, $result);
        $this->assertSame($trooper_with_attendance->id, $result->first()->id);
    }

    public function test_invoke_excludes_troopers_with_only_non_attended_status(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper   = Trooper::factory()->asMember()->create();

        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(10))->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asGoing()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator));

        $this->assertCount(0, $result);
    }

    public function test_invoke_excludes_attended_shifts_on_non_closed_events(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper   = Trooper::factory()->asMember()->create();

        $event = Event::factory()->withEventStart(now()->subDays(10))->create(); // open, not closed
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator));

        $this->assertCount(0, $result);
    }

    // -------------------------------------------------------------------------
    // moderatedBy scope
    // -------------------------------------------------------------------------

    public function test_invoke_respects_moderatedby_scope(): void
    {
        $org       = Organization::factory()->create();
        $other_org = Organization::factory()->create();

        $moderator = Trooper::factory()->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $visible_trooper = Trooper::factory()->asMember()->create();
        TrooperAssignment::factory()->forTrooper($visible_trooper)->forOrganization($org)->asMember()->create();

        $hidden_trooper = Trooper::factory()->asMember()->create();
        TrooperAssignment::factory()->forTrooper($hidden_trooper)->forOrganization($other_org)->asMember()->create();

        $event  = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($visible_trooper)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($hidden_trooper)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator));

        $this->assertCount(1, $result);
        $this->assertSame($visible_trooper->id, $result->first()->id);
    }

    // -------------------------------------------------------------------------
    // event_shifts_count subquery
    // -------------------------------------------------------------------------

    public function test_invoke_adds_event_shifts_count_property(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper   = Trooper::factory()->asMember()->create();

        $event  = Event::factory()->asClosed()->withEventStart(now()->subDays(10))->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator));

        $this->assertTrue(isset($result->first()->event_shifts_count));
        $this->assertSame(2, $result->first()->event_shifts_count);
    }

    public function test_invoke_event_shifts_count_excludes_non_attended_status(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper   = Trooper::factory()->asMember()->create();

        $event  = Event::factory()->asClosed()->withEventStart(now()->subDays(10))->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->asGoing()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator));

        $this->assertSame(1, $result->first()->event_shifts_count);
    }

    // -------------------------------------------------------------------------
    // events_count subquery
    // -------------------------------------------------------------------------

    public function test_invoke_adds_events_count_property(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper   = Trooper::factory()->asMember()->create();

        $event1 = Event::factory()->asClosed()->withEventStart(now()->subDays(10))->create();
        $event2 = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift1 = EventShift::factory()->forEvent($event1)->create();
        $shift2 = EventShift::factory()->forEvent($event2)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator));

        $this->assertTrue(isset($result->first()->events_count));
        $this->assertSame(2, $result->first()->events_count);
    }

    public function test_invoke_events_count_deduplicates_across_shifts_for_same_event(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper   = Trooper::factory()->asMember()->create();

        $event  = Event::factory()->asClosed()->withEventStart(now()->subDays(10))->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator));

        $this->assertSame(1, $result->first()->events_count);
        $this->assertSame(2, $result->first()->event_shifts_count);
    }

    // -------------------------------------------------------------------------
    // Date range filtering
    // -------------------------------------------------------------------------

    public function test_invoke_respects_date_start(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper   = Trooper::factory()->asMember()->create();

        $event1 = Event::factory()->asClosed()->withEventStart(Carbon::parse('2026-02-15'))->create();
        $event2 = Event::factory()->asClosed()->withEventStart(Carbon::parse('2026-01-15'))->create();
        $shift1 = EventShift::factory()->forEvent($event1)->create();
        $shift2 = EventShift::factory()->forEvent($event2)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator, date_start: Carbon::parse('2026-02-01')));

        $this->assertSame(1, $result->first()->events_count);
        $this->assertSame(1, $result->first()->event_shifts_count);
    }

    public function test_invoke_respects_date_end(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper   = Trooper::factory()->asMember()->create();

        $event1 = Event::factory()->asClosed()->withEventStart(Carbon::parse('2026-01-15'))->create();
        $event2 = Event::factory()->asClosed()->withEventStart(Carbon::parse('2026-03-15'))->create();
        $shift1 = EventShift::factory()->forEvent($event1)->create();
        $shift2 = EventShift::factory()->forEvent($event2)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator, date_end: Carbon::parse('2026-02-01')));

        $this->assertSame(1, $result->first()->events_count);
        $this->assertSame(1, $result->first()->event_shifts_count);
    }

    public function test_invoke_date_filter_excludes_trooper_entirely_when_no_matching_events(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper   = Trooper::factory()->asMember()->create();

        $event = Event::factory()->asClosed()->withEventStart(Carbon::parse('2025-06-01'))->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator, date_start: Carbon::parse('2026-01-01')));

        $this->assertCount(0, $result);
    }

    // -------------------------------------------------------------------------
    // active_only filter
    // -------------------------------------------------------------------------

    public function test_invoke_filters_active_members_only(): void
    {
        $moderator       = Trooper::factory()->asAdministrator()->create();
        $active_trooper  = Trooper::factory()->asMember()->create();
        $retired_trooper = Trooper::factory()->asRetired()->create();

        $event  = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($active_trooper)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($retired_trooper)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator, active_only: true));

        $this->assertCount(1, $result);
        $this->assertSame($active_trooper->id, $result->first()->id);
    }

    public function test_invoke_active_only_false_includes_retired_troopers(): void
    {
        $moderator       = Trooper::factory()->asAdministrator()->create();
        $active_trooper  = Trooper::factory()->asMember()->create();
        $retired_trooper = Trooper::factory()->asRetired()->create();

        $event  = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($active_trooper)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($retired_trooper)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator, active_only: false));

        $this->assertCount(2, $result);
    }

    // -------------------------------------------------------------------------
    // Organization filter (specific club)
    // -------------------------------------------------------------------------

    public function test_invoke_organization_filter_counts_only_matching_org_shifts(): void
    {
        $this->skipIfSqlite();
        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper   = Trooper::factory()->asMember()->create();
        $org       = Organization::factory()->create();

        $event  = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->asAttended()
            ->withCostumeOrganizationIds([$org->id])->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->asAttended()
            ->withCostumeOrganizationIds([999])->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator, organization: $org));

        $this->assertCount(1, $result);
        $this->assertSame(1, $result->first()->event_shifts_count);
    }

    public function test_invoke_organization_filter_excludes_trooper_with_no_org_shifts(): void
    {
        $this->skipIfSqlite();
        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper   = Trooper::factory()->asMember()->create();
        $org       = Organization::factory()->create();

        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()
            ->withCostumeOrganizationIds([999])->create(); // different org only

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator, organization: $org));

        $this->assertCount(0, $result);
    }

    // -------------------------------------------------------------------------
    // accessible_org_ids filter (all clubs)
    // -------------------------------------------------------------------------

    public function test_invoke_accessible_org_ids_filters_counts_to_accessible_orgs(): void
    {
        $this->skipIfSqlite();
        $moderator       = Trooper::factory()->asAdministrator()->create();
        $trooper         = Trooper::factory()->asMember()->create();
        $accessible_org  = Organization::factory()->create();
        $other_org       = Organization::factory()->create();

        $event  = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->asAttended()
            ->withCostumeOrganizationIds([$accessible_org->id])->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->asAttended()
            ->withCostumeOrganizationIds([$other_org->id])->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator, accessible_org_ids: [$accessible_org->id]));

        $this->assertCount(1, $result);
        $this->assertSame(1, $result->first()->event_shifts_count);
    }

    public function test_invoke_accessible_org_ids_excludes_trooper_with_no_accessible_org_shifts(): void
    {
        $this->skipIfSqlite();
        $moderator      = Trooper::factory()->asAdministrator()->create();
        $trooper        = Trooper::factory()->asMember()->create();
        $accessible_org = Organization::factory()->create();
        $other_org      = Organization::factory()->create();

        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()
            ->withCostumeOrganizationIds([$other_org->id])->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator, accessible_org_ids: [$accessible_org->id]));

        $this->assertCount(0, $result);
    }

    public function test_invoke_empty_accessible_org_ids_applies_no_costume_filter(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper   = Trooper::factory()->asMember()->create();

        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()
            ->withCostumeOrganizationIds([999])->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator, accessible_org_ids: []));

        $this->assertCount(1, $result);
        $this->assertSame(1, $result->first()->event_shifts_count);
    }

    // -------------------------------------------------------------------------
    // Sorting
    // -------------------------------------------------------------------------

    public function test_invoke_default_sort_is_event_shifts_count_descending(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $few       = Trooper::factory()->asMember()->create();
        $many      = Trooper::factory()->asMember()->create();

        $event  = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();
        $shift3 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($few)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($many)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift3)->forTrooper($many)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator));

        $this->assertSame($many->id, $result->first()->id);
        $this->assertSame($few->id, $result->last()->id);
    }

    public function test_invoke_sort_by_display_name_ascending(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $b         = Trooper::factory()->asMember()->withDisplayName('Beta Trooper')->create();
        $a         = Trooper::factory()->asMember()->withDisplayName('Alpha Trooper')->create();

        $event  = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($b)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($a)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator, sort: 'display_name', dir: 'asc'));

        $this->assertSame($a->id, $result->first()->id);
        $this->assertSame($b->id, $result->last()->id);
    }

    public function test_invoke_sort_by_events_count_descending(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $few       = Trooper::factory()->asMember()->create();
        $many      = Trooper::factory()->asMember()->create();

        $event1 = Event::factory()->asClosed()->withEventStart(now()->subDays(10))->create();
        $event2 = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift1 = EventShift::factory()->forEvent($event1)->create();
        $shift2 = EventShift::factory()->forEvent($event1)->create();
        $shift3 = EventShift::factory()->forEvent($event2)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($few)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($many)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift3)->forTrooper($many)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator, sort: 'events_count', dir: 'desc'));

        $this->assertSame($many->id, $result->first()->id);
        $this->assertSame($few->id, $result->last()->id);
    }

    public function test_invoke_sort_by_event_shifts_count_ascending(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $few       = Trooper::factory()->asMember()->create();
        $many      = Trooper::factory()->asMember()->create();

        $event  = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();
        $shift3 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($few)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($many)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift3)->forTrooper($many)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator, sort: 'event_shifts_count', dir: 'asc'));

        $this->assertSame($few->id, $result->first()->id);
        $this->assertSame($many->id, $result->last()->id);
    }

    public function test_invoke_falls_back_to_event_shifts_count_for_invalid_sort(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $few       = Trooper::factory()->asMember()->create();
        $many      = Trooper::factory()->asMember()->create();

        $event  = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();
        $shift3 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($few)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($many)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift3)->forTrooper($many)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator, sort: 'injected_column', dir: 'desc'));

        $this->assertSame($many->id, $result->first()->id);
    }

    public function test_invoke_falls_back_to_desc_for_invalid_dir(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();
        $few       = Trooper::factory()->asMember()->create();
        $many      = Trooper::factory()->asMember()->create();

        $event  = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();
        $shift3 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($few)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($many)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift3)->forTrooper($many)->asAttended()->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator, sort: 'event_shifts_count', dir: 'DROP TABLE'));

        $this->assertSame($many->id, $result->first()->id);
    }

    // -------------------------------------------------------------------------
    // Pagination
    // -------------------------------------------------------------------------

    // -------------------------------------------------------------------------
    // Org attribution rules
    // -------------------------------------------------------------------------

    public function test_invoke_organization_filter_rule1_counts_explicit_organization_id(): void
    {
        $this->skipIfSqlite();
        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper   = Trooper::factory()->asMember()->create();
        $org       = Organization::factory()->create();

        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()
            ->state(fn () => [EventTrooper::ORGANIZATION_ID => $org->id])->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator, organization: $org));

        $this->assertCount(1, $result);
        $this->assertSame(1, $result->first()->event_shifts_count);
    }

    public function test_invoke_organization_filter_rule1_excludes_trooper_with_different_explicit_org(): void
    {
        $this->skipIfSqlite();
        $moderator   = Trooper::factory()->asAdministrator()->create();
        $trooper     = Trooper::factory()->asMember()->create();
        $target_org  = Organization::factory()->create();
        $other_org   = Organization::factory()->create();

        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()
            ->state(fn () => [EventTrooper::ORGANIZATION_ID => $other_org->id])->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator, organization: $target_org));

        $this->assertCount(0, $result);
    }

    public function test_invoke_join_date_after_event_start_is_still_counted(): void
    {
        $this->skipIfSqlite();
        $moderator = Trooper::factory()->asAdministrator()->create();
        $trooper   = Trooper::factory()->asMember()->create();
        $org       = Organization::factory()->withNodePath('501:')->create();

        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($org)
            ->state(fn () => [TrooperOrganization::JOIN_DATE => Carbon::parse('2026-06-01')])
            ->create();

        $event = Event::factory()->asClosed()->withEventStart(Carbon::parse('2026-01-01'))->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()
            ->withCostumeOrganizationIds([$org->id])->create();

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator, organization: $org));

        $this->assertCount(1, $result);
        $this->assertSame(1, $result->first()->event_shifts_count);
    }

    // -------------------------------------------------------------------------
    // Pagination
    // -------------------------------------------------------------------------

    public function test_invoke_paginates_results(): void
    {
        $moderator = Trooper::factory()->asAdministrator()->create();

        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();

        foreach (range(1, 5) as $i) {
            $trooper = Trooper::factory()->asMember()->create();
            $shift   = EventShift::factory()->forEvent($event)->create();
            EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()->create();
        }

        $subject = new GetTrooperEventSummaryQueryHandler();
        $result  = $subject(new GetTrooperEventSummaryQuery($moderator, page_size: 2));

        $this->assertSame(2, $result->perPage());
        $this->assertSame(5, $result->total());
        $this->assertCount(2, $result->items());
    }
}
