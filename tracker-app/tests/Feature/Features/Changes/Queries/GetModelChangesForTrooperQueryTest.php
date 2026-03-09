<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Changes\Queries;

use App\Features\Changes\Queries\GetModelChangesForTrooperQuery;
use App\Models\Trooper;
use Carbon\Carbon;
use Tests\TestCase;

class GetModelChangesForTrooperQueryTest extends TestCase
{
    public function test_construct_stores_trooper_and_lookback_as_int(): void
    {
        $trooper = new Trooper();

        $subject = new GetModelChangesForTrooperQuery($trooper, 7);

        $this->assertSame($trooper, $subject->trooper);
        $this->assertSame(7, $subject->lookback);
    }

    public function test_construct_stores_trooper_and_lookback_as_string(): void
    {
        $trooper = new Trooper();

        $subject = new GetModelChangesForTrooperQuery($trooper, '2026-01-01');

        $this->assertSame($trooper, $subject->trooper);
        $this->assertSame('2026-01-01', $subject->lookback);
    }

    public function test_construct_stores_trooper_and_lookback_as_carbon(): void
    {
        $trooper = new Trooper();
        $date = Carbon::parse('2026-01-01');

        $subject = new GetModelChangesForTrooperQuery($trooper, $date);

        $this->assertSame($trooper, $subject->trooper);
        $this->assertSame($date, $subject->lookback);
    }
}
