<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Features\Events\Queries\GetEventDisplayQuery;
use App\Models\Event;
use App\Models\Trooper;
use Tests\TestCase;

class GetEventDisplayQueryTest extends TestCase
{
    public function test_construct_stores_event_and_trooper(): void
    {
        $event = new Event();
        $trooper = new Trooper();

        $subject = new GetEventDisplayQuery($event, $trooper);

        $this->assertSame($event, $subject->event);
        $this->assertSame($trooper, $subject->trooper);
    }
}
