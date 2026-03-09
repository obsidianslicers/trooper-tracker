<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTrooperMembershipsQuery;
use App\Models\Trooper;
use Tests\TestCase;

class GetTrooperMembershipsQueryTest extends TestCase
{
    public function test_construct_stores_trooper(): void
    {
        $trooper = new Trooper();

        $subject = new GetTrooperMembershipsQuery($trooper);

        $this->assertSame($trooper, $subject->trooper);
    }
}
