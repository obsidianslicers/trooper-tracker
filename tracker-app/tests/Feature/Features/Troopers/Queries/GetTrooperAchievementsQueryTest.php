<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTrooperAchievementsQuery;
use Carbon\Carbon;
use Tests\TestCase;

class GetTrooperAchievementsQueryTest extends TestCase
{
    public function test_construct_stores_lookback_as_int(): void
    {
        $subject = new GetTrooperAchievementsQuery(30);

        $this->assertSame(30, $subject->lookback);
    }

    public function test_construct_stores_lookback_as_string(): void
    {
        $subject = new GetTrooperAchievementsQuery('2026-01-01');

        $this->assertSame('2026-01-01', $subject->lookback);
    }

    public function test_construct_stores_lookback_as_carbon(): void
    {
        $date = Carbon::parse('2026-01-01');

        $subject = new GetTrooperAchievementsQuery($date);

        $this->assertSame($date, $subject->lookback);
    }

    public function test_parse_lookback_converts_int_to_carbon(): void
    {
        $subject = new GetTrooperAchievementsQuery(14);

        $result = $subject->parseLookback();

        $this->assertInstanceOf(Carbon::class, $result);
    }

    public function test_parse_lookback_returns_carbon_unchanged(): void
    {
        $date = Carbon::parse('2026-02-01 08:00:00');

        $subject = new GetTrooperAchievementsQuery($date);

        $result = $subject->parseLookback();

        $this->assertSame($date, $result);
    }
}
