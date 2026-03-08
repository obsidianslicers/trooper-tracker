<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Notices\Queries;

use App\Features\Notices\Queries\GetTrooperNoticesQuery;
use App\Models\Trooper;
use Tests\TestCase;

class GetTrooperNoticesQueryTest extends TestCase
{
    public function test_construct_stores_trooper(): void
    {
        $trooper = new Trooper();

        $subject = new GetTrooperNoticesQuery($trooper);

        $this->assertSame($trooper, $subject->trooper);
    }
}
