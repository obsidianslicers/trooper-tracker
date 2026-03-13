<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTrooperAwardsQuery;
use Carbon\Carbon;
use Tests\TestCase;

class GetTrooperAwardsQueryTest extends TestCase
{
    public function test_construct_stores_lookback_as_int(): void
    {
        $subject = new GetTrooperAwardsQuery(30);

        $this->assertSame(30, $subject->lookback);
    }

    public function test_construct_stores_lookback_as_string(): void
    {
        $subject = new GetTrooperAwardsQuery('2026-01-01');

        $this->assertSame('2026-01-01', $subject->lookback);
    }

    public function test_construct_stores_lookback_as_carbon(): void
    {
        $date = Carbon::parse('2026-01-01');

        $subject = new GetTrooperAwardsQuery($date);

        $this->assertSame($date, $subject->lookback);
    }

    public function test_parse_lookback_converts_int_to_carbon(): void
    {
        $subject = new GetTrooperAwardsQuery(7);

        $result = $subject->parseLookback();

        $this->assertInstanceOf(Carbon::class, $result);
    }

    public function test_parse_lookback_converts_string_to_carbon(): void
    {
        $subject = new GetTrooperAwardsQuery('2026-01-01 10:20:30');

        $result = $subject->parseLookback();

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertSame('2026-01-01 10:20:30', $result->format('Y-m-d H:i:s'));
    }
}
