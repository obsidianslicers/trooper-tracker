<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Features\Reports\Queries\GetCostumeEventSummaryQuery;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Tests\TestCase;

class GetCostumeEventSummaryQueryTest extends TestCase
{
    public function test_construct_stores_moderator(): void
    {
        $trooper = new Trooper();

        $subject = new GetCostumeEventSummaryQuery($trooper);

        $this->assertSame($trooper, $subject->moderator);
    }

    public function test_construct_defaults_to_null_dates(): void
    {
        $subject = new GetCostumeEventSummaryQuery(new Trooper());

        $this->assertNull($subject->date_start);
        $this->assertNull($subject->date_end);
    }

    public function test_construct_stores_date_range(): void
    {
        $start = Carbon::parse('2026-01-01');
        $end   = Carbon::parse('2026-03-31');

        $subject = new GetCostumeEventSummaryQuery(new Trooper(), $start, $end);

        $this->assertSame($start, $subject->date_start);
        $this->assertSame($end, $subject->date_end);
    }

    public function test_construct_defaults_page_size_to_50(): void
    {
        $subject = new GetCostumeEventSummaryQuery(new Trooper());

        $this->assertSame(50, $subject->page_size);
    }

    public function test_construct_stores_page_size(): void
    {
        $subject = new GetCostumeEventSummaryQuery(new Trooper(), page_size: PHP_INT_MAX);

        $this->assertSame(PHP_INT_MAX, $subject->page_size);
    }

    public function test_construct_defaults_organization_to_null(): void
    {
        $subject = new GetCostumeEventSummaryQuery(new Trooper());

        $this->assertNull($subject->organization);
    }

    public function test_construct_stores_organization(): void
    {
        $org = new Organization();

        $subject = new GetCostumeEventSummaryQuery(new Trooper(), organization: $org);

        $this->assertSame($org, $subject->organization);
    }

    public function test_construct_defaults_sort_and_dir(): void
    {
        $subject = new GetCostumeEventSummaryQuery(new Trooper());

        $this->assertSame('uses_count', $subject->sort);
        $this->assertSame('desc', $subject->dir);
    }

    public function test_construct_stores_sort_and_dir(): void
    {
        $subject = new GetCostumeEventSummaryQuery(new Trooper(), sort: 'name', dir: 'asc');

        $this->assertSame('name', $subject->sort);
        $this->assertSame('asc', $subject->dir);
    }

    public function test_construct_defaults_accessible_org_ids_to_empty_array(): void
    {
        $subject = new GetCostumeEventSummaryQuery(new Trooper());

        $this->assertSame([], $subject->accessible_org_ids);
    }

    public function test_construct_stores_accessible_org_ids(): void
    {
        $subject = new GetCostumeEventSummaryQuery(new Trooper(), accessible_org_ids: [1, 2, 3]);

        $this->assertSame([1, 2, 3], $subject->accessible_org_ids);
    }
}
