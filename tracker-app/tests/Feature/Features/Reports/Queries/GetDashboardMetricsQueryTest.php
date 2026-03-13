<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Features\Reports\Queries\GetDashboardMetricsQuery;
use Carbon\Carbon;
use Tests\TestCase;

class GetDashboardMetricsQueryTest extends TestCase
{
    public function test_construct_stores_lookback_as_int(): void
    {
        $subject = new GetDashboardMetricsQuery(30);

        $this->assertSame(30, $subject->lookback);
    }

    public function test_construct_stores_lookback_as_string(): void
    {
        $subject = new GetDashboardMetricsQuery('2026-01-01');

        $this->assertSame('2026-01-01', $subject->lookback);
    }

    public function test_construct_stores_lookback_as_carbon(): void
    {
        $date = Carbon::parse('2026-01-01');

        $subject = new GetDashboardMetricsQuery($date);

        $this->assertSame($date, $subject->lookback);
    }

    public function test_parse_lookback_converts_int_to_carbon(): void
    {
        $subject = new GetDashboardMetricsQuery(7);

        $result = $subject->parseLookback();

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals(now()->subDays(7)->format('Y-m-d'), $result->format('Y-m-d'));
    }

    public function test_parse_lookback_converts_string_to_carbon(): void
    {
        $subject = new GetDashboardMetricsQuery('2026-02-15');

        $result = $subject->parseLookback();

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertSame('2026-02-15', $result->format('Y-m-d'));
    }

    public function test_parse_lookback_returns_carbon_unchanged(): void
    {
        $date = Carbon::parse('2026-02-15');

        $subject = new GetDashboardMetricsQuery($date);

        $result = $subject->parseLookback();

        $this->assertSame($date, $result);
    }
}
