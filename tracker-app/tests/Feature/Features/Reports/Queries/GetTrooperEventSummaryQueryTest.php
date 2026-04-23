<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Features\Reports\Queries\GetTrooperEventSummaryQuery;
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

    public function test_construct_stores_date_range(): void
    {
        $start = Carbon::parse('2026-01-01');
        $end = Carbon::parse('2026-03-31');

        $subject = new GetTrooperEventSummaryQuery(new Trooper(), $start, $end);

        $this->assertSame($start, $subject->date_start);
        $this->assertSame($end, $subject->date_end);
    }

    public function test_construct_defaults_to_null_dates_and_false_active_only(): void
    {
        $subject = new GetTrooperEventSummaryQuery(new Trooper());

        $this->assertNull($subject->date_start);
        $this->assertNull($subject->date_end);
        $this->assertFalse($subject->active_only);
    }

    public function test_construct_stores_active_only_flag(): void
    {
        $subject = new GetTrooperEventSummaryQuery(new Trooper(), active_only: true);

        $this->assertTrue($subject->active_only);
    }
}
