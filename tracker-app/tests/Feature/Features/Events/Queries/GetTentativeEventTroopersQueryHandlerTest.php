<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Enums\EventStatus;
use App\Features\Events\Queries\GetTentativeEventTroopersQuery;
use App\Features\Events\Queries\GetTentativeEventTroopersQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTentativeEventTroopersQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_tentative_troopers_for_open_events_within_seven_days(): void
    {
        $event = Event::factory()->withEventStart(now()->addDays(3))->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->asMember()->create();
        $event_trooper = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asTentative()->create();

        $subject = new GetTentativeEventTroopersQueryHandler();
        $result = $subject(new GetTentativeEventTroopersQuery());

        $this->assertCount(1, $result);
        $this->assertSame($event_trooper->id, $result->first()->id);
    }

    public function test_invoke_excludes_non_tentative_troopers(): void
    {
        $event = Event::factory()->withEventStart(now()->addDays(3))->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->asMember()->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asGoing()->create();

        $subject = new GetTentativeEventTroopersQueryHandler();
        $result = $subject(new GetTentativeEventTroopersQuery());

        $this->assertCount(0, $result);
    }

    public function test_invoke_excludes_events_beyond_seven_days(): void
    {
        $event = Event::factory()->withEventStart(now()->addDays(8))->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->asMember()->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asTentative()->create();

        $subject = new GetTentativeEventTroopersQueryHandler();
        $result = $subject(new GetTentativeEventTroopersQuery());

        $this->assertCount(0, $result);
    }

    public function test_invoke_excludes_past_events(): void
    {
        $event = Event::factory()->withEventStart(now()->subDay())->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->asMember()->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asTentative()->create();

        $subject = new GetTentativeEventTroopersQueryHandler();
        $result = $subject(new GetTentativeEventTroopersQuery());

        $this->assertCount(0, $result);
    }

    public function test_invoke_excludes_non_open_events(): void
    {
        $event = Event::factory()->withEventStart(now()->addDays(3))->create([
            Event::STATUS => EventStatus::CLOSED,
        ]);
        $shift = EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->asMember()->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asTentative()->create();

        $subject = new GetTentativeEventTroopersQueryHandler();
        $result = $subject(new GetTentativeEventTroopersQuery());

        $this->assertCount(0, $result);
    }

    public function test_invoke_eager_loads_trooper_and_event_shift_relations(): void
    {
        $event = Event::factory()->withEventStart(now()->addDays(3))->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->asMember()->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asTentative()->create();

        $subject = new GetTentativeEventTroopersQueryHandler();
        $result = $subject(new GetTentativeEventTroopersQuery());

        $this->assertTrue($result->first()->relationLoaded('trooper'));
        $this->assertTrue($result->first()->relationLoaded('event_shift'));
    }
}
