<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Features\Reports\Queries\GetDonationEventSummaryQuery;
use App\Features\Reports\Queries\GetDonationEventSummaryQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetDonationEventSummaryQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Basic filtering
    // -------------------------------------------------------------------------

    public function test_invoke_returns_closed_events(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $closed = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(5))->create();
        Event::factory()->withOrganization($org)->withEventStart(now()->subDays(5))->create(); // open

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator));

        $this->assertCount(1, $result);
        $this->assertSame($closed->id, $result->first()->id);
    }

    public function test_invoke_excludes_open_events(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        Event::factory()->withOrganization($org)->withEventStart(now()->subDays(5))->create();

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator));

        $this->assertCount(0, $result);
    }

    public function test_invoke_respects_moderatedby_scope(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $visible = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(5))->create();
        Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create(); // different org

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator));

        $this->assertCount(1, $result);
        $this->assertSame($visible->id, $result->first()->id);
    }

    // -------------------------------------------------------------------------
    // attendees_count subquery
    // -------------------------------------------------------------------------

    public function test_invoke_adds_attendees_count_property(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $event = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(5))->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()->forEventShift($shift)->asAttended()->count(3)->create();

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator));

        $this->assertTrue(isset($result->first()->attendees_count));
        $this->assertSame(3, $result->first()->attendees_count);
    }

    public function test_invoke_attendees_count_deduplicates_across_shifts(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $trooper = Trooper::factory()->asMember()->create();
        $event   = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(5))->create();
        $shift1  = EventShift::factory()->forEvent($event)->create();
        $shift2  = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->asAttended()->create();

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator));

        $this->assertSame(1, $result->first()->attendees_count);
    }

    public function test_invoke_attendees_count_excludes_non_attended_status(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $event = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(5))->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()->forEventShift($shift)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift)->create(); // going status

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator));

        $this->assertSame(1, $result->first()->attendees_count);
    }

    public function test_invoke_attendees_count_is_zero_when_no_attendees(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $event = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(5))->create();
        EventShift::factory()->forEvent($event)->create();

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator));

        $this->assertSame(0, $result->first()->attendees_count);
    }

    // -------------------------------------------------------------------------
    // Date range filtering
    // -------------------------------------------------------------------------

    public function test_invoke_respects_date_start(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $after  = Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-02-15'))->create();
        Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-01-15'))->create();

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator, date_start: Carbon::parse('2026-02-01')->startOfDay()));

        $this->assertCount(1, $result);
        $this->assertSame($after->id, $result->first()->id);
    }

    public function test_invoke_respects_date_end(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $before = Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-01-15'))->create();
        Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-03-15'))->create();

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator, date_end: Carbon::parse('2026-02-01')->endOfDay()));

        $this->assertCount(1, $result);
        $this->assertSame($before->id, $result->first()->id);
    }

    public function test_invoke_respects_date_range_both_bounds(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2025-10-01'))->create();
        $in = Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-01-15'))->create();
        Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-05-01'))->create();

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery(
            $moderator,
            date_start: Carbon::parse('2025-11-01')->startOfDay(),
            date_end: Carbon::parse('2026-04-30')->endOfDay(),
        ));

        $this->assertCount(1, $result);
        $this->assertSame($in->id, $result->first()->id);
    }

    // -------------------------------------------------------------------------
    // charity_only filter
    // -------------------------------------------------------------------------

    public function test_invoke_charity_only_includes_event_with_direct_funds(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $with = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(5))
            ->create([Event::CHARITY_DIRECT_FUNDS => 500, Event::CHARITY_INDIRECT_FUNDS => 0, Event::CHARITY_NAME => null]);
        Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(3))->withoutCharityData()->create();

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator, charity_only: true));

        $this->assertCount(1, $result);
        $this->assertSame($with->id, $result->first()->id);
    }

    public function test_invoke_charity_only_includes_event_with_indirect_funds(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $with = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(5))
            ->create([Event::CHARITY_DIRECT_FUNDS => 0, Event::CHARITY_INDIRECT_FUNDS => 750, Event::CHARITY_NAME => null]);
        Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(3))->withoutCharityData()->create();

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator, charity_only: true));

        $this->assertCount(1, $result);
        $this->assertSame($with->id, $result->first()->id);
    }

    public function test_invoke_charity_only_includes_event_with_charity_name(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $with = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(5))
            ->create([Event::CHARITY_DIRECT_FUNDS => 0, Event::CHARITY_INDIRECT_FUNDS => 0, Event::CHARITY_NAME => 'Children\'s Hospital']);
        Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(3))->withoutCharityData()->create();

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator, charity_only: true));

        $this->assertCount(1, $result);
        $this->assertSame($with->id, $result->first()->id);
    }

    public function test_invoke_charity_only_excludes_events_without_charity_data(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(5))->withoutCharityData()->create();

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator, charity_only: true));

        $this->assertCount(0, $result);
    }

    public function test_invoke_charity_only_false_includes_events_without_charity_data(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(5))->withoutCharityData()->create();
        Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(3))
            ->create([Event::CHARITY_DIRECT_FUNDS => 100, Event::CHARITY_INDIRECT_FUNDS => 0, Event::CHARITY_NAME => null]);

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator, charity_only: false));

        $this->assertCount(2, $result);
    }

    // -------------------------------------------------------------------------
    // Sorting
    // -------------------------------------------------------------------------

    public function test_invoke_default_sort_is_event_start_descending(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $older = Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-01-01'))->create();
        $newer = Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-03-01'))->create();

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator));

        $this->assertSame($newer->id, $result->first()->id);
        $this->assertSame($older->id, $result->last()->id);
    }

    public function test_invoke_sort_by_event_start_ascending(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $older = Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-01-01'))->create();
        $newer = Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-03-01'))->create();

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator, sort: 'event_start', dir: 'asc'));

        $this->assertSame($older->id, $result->first()->id);
        $this->assertSame($newer->id, $result->last()->id);
    }

    public function test_invoke_sort_by_name(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $b = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(5))
            ->create([Event::NAME => 'Beta Event']);
        $a = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(3))
            ->create([Event::NAME => 'Alpha Event']);

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator, sort: 'name', dir: 'asc'));

        $this->assertSame($a->id, $result->first()->id);
        $this->assertSame($b->id, $result->last()->id);
    }

    public function test_invoke_sort_by_charity_name(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $b = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(5))
            ->create([Event::CHARITY_NAME => 'Zoo Foundation']);
        $a = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(3))
            ->create([Event::CHARITY_NAME => 'Animal Shelter']);

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator, sort: 'charity_name', dir: 'asc'));

        $this->assertSame($a->id, $result->first()->id);
        $this->assertSame($b->id, $result->last()->id);
    }

    public function test_invoke_sort_by_charity_direct_funds_descending(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $low  = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(5))
            ->create([Event::CHARITY_DIRECT_FUNDS => 100]);
        $high = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(3))
            ->create([Event::CHARITY_DIRECT_FUNDS => 900]);

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator, sort: 'charity_direct_funds', dir: 'desc'));

        $this->assertSame($high->id, $result->first()->id);
        $this->assertSame($low->id, $result->last()->id);
    }

    public function test_invoke_sort_by_charity_indirect_funds_descending(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $low  = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(5))
            ->create([Event::CHARITY_INDIRECT_FUNDS => 200]);
        $high = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(3))
            ->create([Event::CHARITY_INDIRECT_FUNDS => 800]);

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator, sort: 'charity_indirect_funds', dir: 'desc'));

        $this->assertSame($high->id, $result->first()->id);
        $this->assertSame($low->id, $result->last()->id);
    }

    public function test_invoke_sort_by_attendees_count_descending(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $few  = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(5))->create();
        $many = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(3))->create();

        $shiftFew  = EventShift::factory()->forEvent($few)->create();
        $shiftMany = EventShift::factory()->forEvent($many)->create();

        EventTrooper::factory()->forEventShift($shiftFew)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shiftMany)->asAttended()->count(5)->create();

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator, sort: 'attendees_count', dir: 'desc'));

        $this->assertSame($many->id, $result->first()->id);
        $this->assertSame($few->id, $result->last()->id);
    }

    public function test_invoke_falls_back_to_event_start_for_invalid_sort_column(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $older = Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-01-01'))->create();
        $newer = Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-03-01'))->create();

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator, sort: 'injected_column', dir: 'desc'));

        $this->assertSame($newer->id, $result->first()->id);
        $this->assertSame($older->id, $result->last()->id);
    }

    public function test_invoke_falls_back_to_desc_for_invalid_dir(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $older = Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-01-01'))->create();
        $newer = Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-03-01'))->create();

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator, sort: 'event_start', dir: 'DROP TABLE'));

        $this->assertSame($newer->id, $result->first()->id);
        $this->assertSame($older->id, $result->last()->id);
    }

    // -------------------------------------------------------------------------
    // Pagination
    // -------------------------------------------------------------------------

    public function test_invoke_paginates_results(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(5))->count(5)->create();

        $subject = new GetDonationEventSummaryQueryHandler();

        $result = $subject(new GetDonationEventSummaryQuery($moderator, page_size: 2));

        $this->assertSame(2, $result->perPage());
        $this->assertSame(5, $result->total());
        $this->assertCount(2, $result->items());
    }
}
