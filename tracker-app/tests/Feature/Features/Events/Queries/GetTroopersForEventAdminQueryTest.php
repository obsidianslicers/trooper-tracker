<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Features\Events\Queries\GetTroopersForEventAdminQuery;
use App\Models\Event;
use Tests\TestCase;

class GetTroopersForEventAdminQueryTest extends TestCase
{
    public function test_construct_stores_event(): void
    {
        $event = new Event();

        $subject = new GetTroopersForEventAdminQuery($event);

        $this->assertSame($event, $subject->event);
    }
}
