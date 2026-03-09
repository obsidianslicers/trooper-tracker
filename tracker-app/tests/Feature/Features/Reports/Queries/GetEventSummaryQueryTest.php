<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Enums\EventStatus;
use App\Features\Reports\Queries\GetEventSummaryQuery;
use App\Models\Trooper;
use Carbon\Carbon;
use Tests\TestCase;

class GetEventSummaryQueryTest extends TestCase
{
    public function test_construct_stores_moderator_and_lookback(): void
    {
        $trooper = new Trooper();

        $subject = new GetEventSummaryQuery($trooper, 30);

        $this->assertSame($trooper, $subject->moderator);
        $this->assertSame(30, $subject->lookback);
    }

    public function test_construct_defaults_show_all_to_false(): void
    {
        $subject = new GetEventSummaryQuery(new Trooper(), 30);

        $this->assertFalse($subject->show_all);
    }

    public function test_construct_defaults_status_to_closed(): void
    {
        $subject = new GetEventSummaryQuery(new Trooper(), 30);

        $this->assertSame(EventStatus::CLOSED, $subject->status);
    }

    public function test_construct_accepts_optional_parameters(): void
    {
        $subject = new GetEventSummaryQuery(new Trooper(), 30, true, EventStatus::OPEN);

        $this->assertTrue($subject->show_all);
        $this->assertSame(EventStatus::OPEN, $subject->status);
    }

    public function test_parse_lookback_converts_int_to_carbon(): void
    {
        $subject = new GetEventSummaryQuery(new Trooper(), 7);

        $result = $subject->parseLookback();

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals(now()->subDays(7)->format('Y-m-d'), $result->format('Y-m-d'));
    }
}
