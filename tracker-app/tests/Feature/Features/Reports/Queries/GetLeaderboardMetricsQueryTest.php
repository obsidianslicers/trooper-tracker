<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Features\Reports\Queries\GetLeaderboardMetricsQuery;
use App\Models\Organization;
use Carbon\Carbon;
use Tests\TestCase;

class GetLeaderboardMetricsQueryTest extends TestCase
{
    public function test_construct_stores_lookback(): void
    {
        $organization = new Organization;

        $subject = new GetLeaderboardMetricsQuery(30, $organization, 10);

        $this->assertSame(30, $subject->lookback);
        $this->assertSame($organization, $subject->organization);
        $this->assertSame(10, $subject->limit);
    }

    public function test_parse_lookback_converts_int_to_carbon(): void
    {
        $subject = new GetLeaderboardMetricsQuery(7);

        $result = $subject->parseLookback();

        $this->assertInstanceOf(Carbon::class, $result);
    }

    public function test_parse_lookback_returns_null_for_all_time(): void
    {
        $subject = new GetLeaderboardMetricsQuery;

        $this->assertNull($subject->parseLookback());
    }
}
