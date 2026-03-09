<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Features\Events\Queries\GetSharedEventRosterQuery;
use App\Models\EventShare;
use Tests\TestCase;

class GetSharedEventRosterQueryTest extends TestCase
{
    public function test_construct_stores_event_share(): void
    {
        $event_share = new EventShare();

        $subject = new GetSharedEventRosterQuery($event_share);

        $this->assertSame($event_share, $subject->event_share);
    }
}
