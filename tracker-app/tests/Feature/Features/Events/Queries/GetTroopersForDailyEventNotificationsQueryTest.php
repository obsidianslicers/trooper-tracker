<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Features\Events\Queries\GetTroopersForDailyEventNotificationsQuery;
use Tests\TestCase;

class GetTroopersForDailyEventNotificationsQueryTest extends TestCase
{
    public function test_construct_creates_query_instance(): void
    {
        $subject = new GetTroopersForDailyEventNotificationsQuery();

        $this->assertInstanceOf(GetTroopersForDailyEventNotificationsQuery::class, $subject);
    }
}
