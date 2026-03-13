<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetCommandStaffQuery;
use Tests\TestCase;

class GetCommandStaffQueryTest extends TestCase
{
    public function test_construct_creates_query_instance(): void
    {
        $subject = new GetCommandStaffQuery();

        $this->assertInstanceOf(GetCommandStaffQuery::class, $subject);
    }
}
