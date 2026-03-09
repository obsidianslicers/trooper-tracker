<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Features\Events\Queries\GetEventShiftsToCloseQuery;
use App\Features\Events\Queries\GetEventShiftsToCloseQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetEventShiftsToCloseQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_active_shifts_ended_in_past_with_relations(): void
    {
        $event = Event::factory()->create();

        $past_shift = EventShift::factory()->forEvent($event)->withShiftEndsAt(Carbon::parse('2026-03-01 00:00:00'))->create();
        EventShift::factory()->forEvent($event)->withShiftEndsAt(now()->addDay())->create();

        $trooper = Trooper::factory()->asMember()->create();
        EventTrooper::factory()->forEventShift($past_shift)->forTrooper($trooper)->create();

        $subject = new GetEventShiftsToCloseQueryHandler();

        $result = $subject(new GetEventShiftsToCloseQuery());

        $this->assertCount(1, $result);
        $this->assertSame($past_shift->id, $result->first()->id);
        $this->assertTrue($result->first()->relationLoaded('event'));
    }
}
