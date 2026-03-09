<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTrooperServiceRecordQuery;
use Tests\TestCase;

class GetTrooperServiceRecordQueryTest extends TestCase
{
    public function test_construct_stores_trooper_id(): void
    {
        $subject = new GetTrooperServiceRecordQuery(42);

        $this->assertSame(42, $subject->trooper_id);
    }
}
