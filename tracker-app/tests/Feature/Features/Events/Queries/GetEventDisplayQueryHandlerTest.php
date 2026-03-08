<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Features\Events\Queries\GetEventDisplayQuery;
use App\Features\Events\Queries\GetEventDisplayQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetEventDisplayQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_event_with_shifts_and_sorted_troopers(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $viewer = Trooper::factory()->asMember()->create();
        $later = Trooper::factory()->asMember()->create();
        $earlier = Trooper::factory()->asMember()->create();

        EventTrooper::factory()->forEventShift($shift)->forTrooper($later)->withSignedUpAt(Carbon::parse('2026-03-10 11:00:00'))->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($earlier)->withSignedUpAt(Carbon::parse('2026-03-10 10:00:00'))->create();

        $subject = new GetEventDisplayQueryHandler();

        $result = $subject(new GetEventDisplayQuery($event, $viewer));

        $this->assertSame($event->id, $result->id);
        $this->assertCount(1, $result->event_shifts);
        $this->assertSame($earlier->id, $result->event_shifts->first()->event_troopers->first()->trooper_id);
    }
}
