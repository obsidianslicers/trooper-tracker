<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Features\Reports\Queries\GetTrooperEventSummaryQuery;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Tests\TestCase;

class GetTrooperEventSummaryQueryTest extends TestCase
{
    public function test_construct_stores_moderator(): void
    {
        $trooper = new Trooper();

        $subject = new GetTrooperEventSummaryQuery($trooper);

        $this->assertSame($trooper, $subject->moderator);
    }

    public function test_construct_defaults_to_null_dates(): void
    {
        $subject = new GetTrooperEventSummaryQuery(new Trooper());

        $this->assertNull($subject->date_start);
        $this->assertNull($subject->date_end);
    }

    public function test_construct_stores_date_range(): void
    {
        $start = Carbon::parse('2026-01-01');
        $end   = Carbon::parse('2026-03-31');

        $subject = new GetTrooperEventSummaryQuery(new Trooper(), $start, $end);

        $this->assertSame($start, $subject->date_start);
        $this->assertSame($end, $subject->date_end);
    }

    public function test_construct_defaults_active_only_to_false(): void
    {
        $subject = new GetTrooperEventSummaryQuery(new Trooper());

        $this->assertFalse($subject->active_only);
    }

    public function test_construct_stores_active_only_flag(): void
    {
        $subject = new GetTrooperEventSummaryQuery(new Trooper(), active_only: true);

        $this->assertTrue($subject->active_only);
    }

    public function test_construct_defaults_page_size_to_50(): void
    {
        $subject = new GetTrooperEventSummaryQuery(new Trooper());

        $this->assertSame(50, $subject->page_size);
    }

    public function test_construct_stores_page_size(): void
    {
        $subject = new GetTrooperEventSummaryQuery(new Trooper(), page_size: PHP_INT_MAX);

        $this->assertSame(PHP_INT_MAX, $subject->page_size);
    }

    public function test_construct_defaults_organization_to_null(): void
    {
        $subject = new GetTrooperEventSummaryQuery(new Trooper());

        $this->assertNull($subject->organization);
    }

    public function test_construct_stores_organization(): void
    {
        $org = new Organization();

        $subject = new GetTrooperEventSummaryQuery(new Trooper(), organization: $org);

        $this->assertSame($org, $subject->organization);
    }

    public function test_construct_defaults_sort_to_event_shifts_count(): void
    {
        $subject = new GetTrooperEventSummaryQuery(new Trooper());

        $this->assertSame('event_shifts_count', $subject->sort);
    }

    public function test_construct_defaults_dir_to_desc(): void
    {
        $subject = new GetTrooperEventSummaryQuery(new Trooper());

        $this->assertSame('desc', $subject->dir);
    }

    public function test_construct_stores_sort_and_dir(): void
    {
        $subject = new GetTrooperEventSummaryQuery(new Trooper(), sort: 'display_name', dir: 'asc');

        $this->assertSame('display_name', $subject->sort);
        $this->assertSame('asc', $subject->dir);
    }

    public function test_construct_defaults_accessible_org_ids_to_empty_array(): void
    {
        $subject = new GetTrooperEventSummaryQuery(new Trooper());

        $this->assertSame([], $subject->accessible_org_ids);
    }

    public function test_construct_stores_accessible_org_ids(): void
    {
        $subject = new GetTrooperEventSummaryQuery(new Trooper(), accessible_org_ids: [1, 2, 3]);

        $this->assertSame([1, 2, 3], $subject->accessible_org_ids);
    }
}
