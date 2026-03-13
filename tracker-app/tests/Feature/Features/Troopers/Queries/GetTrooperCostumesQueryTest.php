<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTrooperCostumesQuery;
use App\Models\Trooper;
use Tests\TestCase;

class GetTrooperCostumesQueryTest extends TestCase
{
    public function test_construct_stores_trooper_and_defaults_organization_ids_to_null(): void
    {
        $trooper = new Trooper();

        $subject = new GetTrooperCostumesQuery($trooper);

        $this->assertSame($trooper, $subject->trooper);
        $this->assertNull($subject->organization_ids);
    }

    public function test_construct_accepts_organization_ids(): void
    {
        $subject = new GetTrooperCostumesQuery(new Trooper(), [1, 2, 3]);

        $this->assertSame([1, 2, 3], $subject->organization_ids);
    }
}
