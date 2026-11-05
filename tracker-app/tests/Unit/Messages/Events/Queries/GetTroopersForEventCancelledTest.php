<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Queries;

use App\Messages\Events\Queries\GetTroopersForEventCancelled;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTroopersForEventCancelledTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_active_troopers_going_to_cancelled_event(): void
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

        $result = (new GetTroopersForEventCancelled($event))->handle();

        $this->assertCount(1, $result);
        $this->assertSame($going_trooper->id, $result->first()->id);
    }
}
