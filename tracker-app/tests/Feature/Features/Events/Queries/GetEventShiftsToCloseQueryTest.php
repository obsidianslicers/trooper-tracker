<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Features\Events\Queries\GetEventShiftsToCloseQuery;
use Tests\TestCase;

class GetEventShiftsToCloseQueryTest extends TestCase
{
    public function test_construct_creates_query_instance(): void
    {
        $subject = new GetEventShiftsToCloseQuery();

        $this->assertInstanceOf(GetEventShiftsToCloseQuery::class, $subject);
    }
}
