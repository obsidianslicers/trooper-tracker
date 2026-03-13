<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTrooperApprovalsQuery;
use App\Models\Trooper;
use Tests\TestCase;

class GetTrooperApprovalsQueryTest extends TestCase
{
    public function test_construct_stores_moderator(): void
    {
        $moderator = new Trooper();

        $subject = new GetTrooperApprovalsQuery($moderator);

        $this->assertSame($moderator, $subject->moderator);
    }
}
