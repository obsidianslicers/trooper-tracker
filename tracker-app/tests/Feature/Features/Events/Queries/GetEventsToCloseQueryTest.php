<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Features\Events\Queries\GetEventsToCloseQuery;
use Tests\TestCase;

class GetEventsToCloseQueryTest extends TestCase
{
    public function test_construct_creates_query_instance(): void
    {
        $subject = new GetEventsToCloseQuery();

        $this->assertInstanceOf(GetEventsToCloseQuery::class, $subject);
    }
}
