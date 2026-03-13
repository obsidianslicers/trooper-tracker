<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTrooperAssignmentsQuery;
use App\Models\Trooper;
use Tests\TestCase;

class GetTrooperAssignmentsQueryTest extends TestCase
{
    public function test_construct_stores_trooper(): void
    {
        $trooper = new Trooper();

        $subject = new GetTrooperAssignmentsQuery($trooper);

        $this->assertSame($trooper, $subject->trooper);
    }
}
