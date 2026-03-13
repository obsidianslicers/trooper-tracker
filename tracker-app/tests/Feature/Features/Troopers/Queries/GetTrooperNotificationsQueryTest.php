<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTrooperNotificationsQuery;
use App\Models\Trooper;
use Tests\TestCase;

class GetTrooperNotificationsQueryTest extends TestCase
{
    public function test_construct_stores_trooper(): void
    {
        $trooper = new Trooper();

        $subject = new GetTrooperNotificationsQuery($trooper);

        $this->assertSame($trooper, $subject->trooper);
    }
}
