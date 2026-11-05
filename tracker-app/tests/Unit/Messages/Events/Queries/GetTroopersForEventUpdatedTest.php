<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Queries;

use App\Enums\EventTrooperStatus;
use App\Messages\Events\Queries\GetTroopersForEventUpdated;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\EventWatch;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTroopersForEventUpdatedTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_includes_trooper_who_only_watches_event(): void
    {
        $event = Event::factory()->create();
        $watcher = Trooper::factory()->create();
        EventWatch::factory()->forEvent($event)->forTrooper($watcher)->create();

        $result = (new GetTroopersForEventUpdated($event))->handle();

        $this->assertCount(1, $result);
        $this->assertSame($watcher->id, $result->first()->id);
    }

    public function test_handle_includes_trooper_on_roster_with_going_or_tentative_status(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $going = Trooper::factory()->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($going)->asGoing()->create();

        $tentative = Trooper::factory()->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($tentative)->asTentative()->create();

        $result = (new GetTroopersForEventUpdated($event))->handle();

        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $going->id));
        $this->assertTrue($result->contains('id', $tentative->id));
    }

    public function test_handle_excludes_trooper_on_roster_with_cancelled_status(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $cancelled = Trooper::factory()->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($cancelled)
            ->state(['status' => EventTrooperStatus::CANCELLED])
            ->create();

        $result = (new GetTroopersForEventUpdated($event))->handle();

        $this->assertCount(0, $result);
    }

    public function test_handle_returns_watcher_and_roster_trooper_only_once(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $trooper = Trooper::factory()->create();
        EventWatch::factory()->forEvent($event)->forTrooper($trooper)->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asGoing()->create();

        $result = (new GetTroopersForEventUpdated($event))->handle();

        $this->assertCount(1, $result);
        $this->assertSame($trooper->id, $result->first()->id);
    }

    public function test_handle_excludes_unrelated_trooper(): void
    {
        $event = Event::factory()->create();
        Trooper::factory()->create();

        $result = (new GetTroopersForEventUpdated($event))->handle();

        $this->assertCount(0, $result);
    }

    public function test_handle_excludes_trooper_related_to_different_event(): void
    {
        $event = Event::factory()->create();
        $other_event = Event::factory()->create();
        $other_shift = EventShift::factory()->forEvent($other_event)->create();

        $other_watcher = Trooper::factory()->create();
        EventWatch::factory()->forEvent($other_event)->forTrooper($other_watcher)->create();

        $other_roster_trooper = Trooper::factory()->create();
        EventTrooper::factory()->forEventShift($other_shift)->forTrooper($other_roster_trooper)->asGoing()->create();

        $result = (new GetTroopersForEventUpdated($event))->handle();

        $this->assertCount(0, $result);
    }
}
