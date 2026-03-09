<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Features\Reports\Queries\GetLeaderboardMetricsQuery;
use Carbon\Carbon;
use Tests\TestCase;

class GetLeaderboardMetricsQueryTest extends TestCase
{
    public function test_construct_stores_lookback(): void
    {
        $subject = new GetLeaderboardMetricsQuery(30);

        $this->assertSame(30, $subject->lookback);
    }

    public function test_parse_lookback_converts_int_to_carbon(): void
    {
        $subject = new GetLeaderboardMetricsQuery(7);

        $result = $subject->parseLookback();

        $this->assertInstanceOf(Carbon::class, $result);
    }
}
