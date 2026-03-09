<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Features\Events\Queries\GetEventShiftDisplayQuery;
use App\Features\Events\Queries\GetEventShiftDisplayQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetEventShiftDisplayQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_shift_with_troopers_sorted_by_signed_up_at(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $viewer = Trooper::factory()->asMember()->create();
        $later = Trooper::factory()->asMember()->create();
        $earlier = Trooper::factory()->asMember()->create();

        EventTrooper::factory()->forEventShift($shift)->forTrooper($later)->withSignedUpAt(Carbon::parse('2026-03-10 11:00:00'))->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($earlier)->withSignedUpAt(Carbon::parse('2026-03-10 10:00:00'))->create();

        $subject = new GetEventShiftDisplayQueryHandler();

        $result = $subject(new GetEventShiftDisplayQuery($shift, $viewer));

        $this->assertSame($shift->id, $result->id);
        $this->assertSame($earlier->id, $result->event_troopers->first()->trooper_id);
    }
}
