<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Features\Reports\Queries\GetEventTypeCountQuery;
use App\Models\Trooper;
use Carbon\Carbon;
use Tests\TestCase;

class GetEventTypeCountQueryTest extends TestCase
{
    public function test_construct_stores_moderator_and_lookback(): void
    {
        $trooper = new Trooper();

        $subject = new GetEventTypeCountQuery($trooper, 30);

        $this->assertSame($trooper, $subject->moderator);
        $this->assertSame(30, $subject->lookback);
    }

    public function test_parse_lookback_converts_int_to_carbon(): void
    {
        $subject = new GetEventTypeCountQuery(new Trooper(), 7);

        $result = $subject->parseLookback();

        $this->assertInstanceOf(Carbon::class, $result);
    }
}
