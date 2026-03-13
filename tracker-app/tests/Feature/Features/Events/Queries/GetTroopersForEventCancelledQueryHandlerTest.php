<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Features\Events\Queries\GetTroopersForEventCancelledQuery;
use App\Features\Events\Queries\GetTroopersForEventCancelledQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTroopersForEventCancelledQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_active_troopers_going_to_cancelled_event(): void
    {
        $event = Event::factory()->create();
        $other_event = Event::factory()->create();

        $shift = EventShift::factory()->forEvent($event)->create();
        $other_shift = EventShift::factory()->forEvent($other_event)->create();

        $going_trooper = Trooper::factory()->asMember()->create();
        $different_event_trooper = Trooper::factory()->asMember()->create();
        $not_going_trooper = Trooper::factory()->asMember()->create();

        EventTrooper::factory()->forEventShift($shift)->forTrooper($going_trooper)->asGoing()->create();
        EventTrooper::factory()->forEventShift($other_shift)->forTrooper($different_event_trooper)->asGoing()->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($not_going_trooper)->asAttended()->create();

        $subject = new GetTroopersForEventCancelledQueryHandler();

        $result = $subject(new GetTroopersForEventCancelledQuery($event));

        $this->assertCount(1, $result);
        $this->assertSame($going_trooper->id, $result->first()->id);
    }
}
