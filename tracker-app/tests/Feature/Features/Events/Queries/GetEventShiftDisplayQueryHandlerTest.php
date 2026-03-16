<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Features\Events\Queries\GetEventShiftDisplayQuery;
use App\Features\Events\Queries\GetEventShiftDisplayQueryHandler;
use App\Models\Event;
use App\Models\EventGuest;
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

    public function test_invoke_eager_loads_trooper_and_added_by_trooper_for_event_troopers(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $viewer = Trooper::factory()->asMember()->create();
        $trooper = Trooper::factory()->asMember()->create();

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->withSignedUpAt(Carbon::parse('2026-03-10 10:00:00'))
            ->create();

        $subject = new GetEventShiftDisplayQueryHandler();

        $result = $subject(new GetEventShiftDisplayQuery($shift, $viewer));

        $event_trooper = $result->event_troopers->first();

        $this->assertTrue($event_trooper->relationLoaded('trooper'));
        $this->assertTrue($event_trooper->relationLoaded('added_by_trooper'));
    }

    public function test_invoke_returns_guests_sorted_by_name_and_eager_loads_added_by_trooper(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $viewer = Trooper::factory()->asMember()->create();
        $first_submitter = Trooper::factory()->asMember()->withDisplayName('Alpha Submitter')->create();
        $second_submitter = Trooper::factory()->asMember()->withDisplayName('Zulu Submitter')->create();

        EventGuest::factory()
            ->forEventShift($shift)
            ->forTrooper($second_submitter)
            ->withName('Zed Guest')
            ->create();

        EventGuest::factory()
            ->forEventShift($shift)
            ->forTrooper($first_submitter)
            ->withName('Ahsoka Guest')
            ->create();

        $subject = new GetEventShiftDisplayQueryHandler();

        $result = $subject(new GetEventShiftDisplayQuery($shift, $viewer));

        $guest_names = $result->event_guests->pluck(EventGuest::NAME)->all();

        $this->assertSame(['Ahsoka Guest', 'Zed Guest'], $guest_names);
        $this->assertTrue($result->event_guests->first()->relationLoaded('added_by_trooper'));
        $this->assertSame('Alpha Submitter', $result->event_guests->first()->added_by_trooper->display_name);
    }
}
