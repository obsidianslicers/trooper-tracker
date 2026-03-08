<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Features\Events\Queries\GetEventsForDisplayQuery;
use Tests\TestCase;

class GetEventsForDisplayQueryTest extends TestCase
{
    public function test_construct_creates_query_instance(): void
    {
        $subject = new GetEventsForDisplayQuery();

        $this->assertInstanceOf(GetEventsForDisplayQuery::class, $subject);
    }
}
