<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Features\Events\Queries\GetEventShiftDisplayQuery;
use App\Models\EventShift;
use App\Models\Trooper;
use Tests\TestCase;

class GetEventShiftDisplayQueryTest extends TestCase
{
    public function test_construct_stores_event_shift_and_trooper(): void
    {
        $event_shift = new EventShift();
        $trooper = new Trooper();

        $subject = new GetEventShiftDisplayQuery($event_shift, $trooper);

        $this->assertSame($event_shift, $subject->event_shift);
        $this->assertSame($trooper, $subject->trooper);
    }
}
