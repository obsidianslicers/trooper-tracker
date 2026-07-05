<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Features\Reports\Queries\GetDonationEventSummaryQuery;
use App\Models\Trooper;
use Carbon\Carbon;
use Tests\TestCase;

class GetDonationEventSummaryQueryTest extends TestCase
{
    public function test_construct_stores_moderator(): void
    {
        $trooper = new Trooper();

        $subject = new GetDonationEventSummaryQuery($trooper);

        $this->assertSame($trooper, $subject->moderator);
    }

    public function test_construct_defaults_to_null_dates(): void
    {
        $subject = new GetDonationEventSummaryQuery(new Trooper());

        $this->assertNull($subject->date_start);
        $this->assertNull($subject->date_end);
    }

    public function test_construct_stores_date_range(): void
    {
        $start = Carbon::parse('2025-11-01');
        $end   = Carbon::parse('2026-04-23');

        $subject = new GetDonationEventSummaryQuery(new Trooper(), $start, $end);

        $this->assertSame($start, $subject->date_start);
        $this->assertSame($end, $subject->date_end);
    }

    public function test_construct_defaults_charity_only_to_false(): void
    {
        $subject = new GetDonationEventSummaryQuery(new Trooper());

        $this->assertFalse($subject->charity_only);
    }

    public function test_construct_stores_charity_only_flag(): void
    {
        $subject = new GetDonationEventSummaryQuery(new Trooper(), charity_only: true);

        $this->assertTrue($subject->charity_only);
    }

    public function test_construct_defaults_page_size_to_50(): void
    {
        $subject = new GetDonationEventSummaryQuery(new Trooper());

        $this->assertSame(50, $subject->page_size);
    }

    public function test_construct_defaults_page_to_null(): void
    {
        $subject = new GetDonationEventSummaryQuery(new Trooper());

        $this->assertNull($subject->page);
    }

    public function test_construct_defaults_sort_to_event_start(): void
    {
        $subject = new GetDonationEventSummaryQuery(new Trooper());

        $this->assertSame('event_start', $subject->sort);
    }

    public function test_construct_defaults_dir_to_desc(): void
    {
        $subject = new GetDonationEventSummaryQuery(new Trooper());

        $this->assertSame('desc', $subject->dir);
    }

    public function test_construct_defaults_selected_org_ids_to_empty(): void
    {
        $subject = new GetDonationEventSummaryQuery(new Trooper());

        $this->assertSame([], $subject->selected_org_ids);
    }

    public function test_construct_stores_selected_org_ids(): void
    {
        $subject = new GetDonationEventSummaryQuery(new Trooper(), selected_org_ids: [1, 2, 3]);

        $this->assertSame([1, 2, 3], $subject->selected_org_ids);
    }

    public function test_construct_accepts_all_parameters(): void
    {
        $trooper = new Trooper();
        $start   = Carbon::parse('2025-01-01');
        $end     = Carbon::parse('2025-12-31');

        $subject = new GetDonationEventSummaryQuery($trooper, $start, $end, true, 100, 'name', 'asc', [5], [1, 2], 3);

        $this->assertSame($trooper, $subject->moderator);
        $this->assertSame($start, $subject->date_start);
        $this->assertSame($end, $subject->date_end);
        $this->assertTrue($subject->charity_only);
        $this->assertSame(100, $subject->page_size);
        $this->assertSame('name', $subject->sort);
        $this->assertSame('asc', $subject->dir);
        $this->assertSame([5], $subject->selected_org_ids);
        $this->assertSame([1, 2], $subject->accessible_org_ids);
        $this->assertSame(3, $subject->page);
    }
}
