<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Queries;

use App\Features\Events\Queries\GetEventsForDisplayQuery;
use Tests\TestCase;

class GetEventsForDisplayQueryTest extends TestCase
{
    public function test_construct_with_no_parameters(): void
    {
        // Act
        $subject = new GetEventsForDisplayQuery();

        // Assert
        $this->assertInstanceOf(GetEventsForDisplayQuery::class, $subject);
    }
}
