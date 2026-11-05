<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Queries;

use App\Messages\Events\Queries\GetSharedEventRoster;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventShare;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetSharedEventRosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_event_with_shifts_and_sorted_intent_to_go_troopers(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $trooper_b = Trooper::factory()->asMember()->withLegalName('Zulu Alpha')->create();
        $trooper_a = Trooper::factory()->asMember()->withLegalName('Alpha Bravo')->create();

        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper_b)->asTentative()->withSignedUpAt(Carbon::parse('2026-03-10 10:00:00'))->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper_a)->asGoing()->withSignedUpAt(Carbon::parse('2026-03-10 09:00:00'))->create();

        $event_share = EventShare::factory()->forEvent($event)->create();

        $result = (new GetSharedEventRoster($event_share))->handle();

        $this->assertSame($event->id, $result->id);
        $this->assertCount(1, $result->event_shifts);
        $this->assertCount(2, $result->event_shifts->first()->event_troopers);
        $this->assertSame('Alpha Bravo', $result->event_shifts->first()->event_troopers->first()->trooper->legal_name);
        $this->assertSame('Zulu Alpha', $result->event_shifts->first()->event_troopers->last()->trooper->legal_name);
    }

    public function test_handle_returns_only_going_guests_sorted_by_signed_up_at(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->asMember()->create();

        EventGuest::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->withName('Late Guest')
            ->asGoing()
            ->withSignedUpAt(Carbon::parse('2026-03-10 11:00:00'))
            ->create();

        EventGuest::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->withName('Cancelled Guest')
            ->asCancelled()
            ->withSignedUpAt(Carbon::parse('2026-03-10 08:00:00'))
            ->create();

        EventGuest::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->withName('Early Guest')
            ->asGoing()
            ->withSignedUpAt(Carbon::parse('2026-03-10 09:00:00'))
            ->create();

        $event_share = EventShare::factory()->forEvent($event)->create();

        $result = (new GetSharedEventRoster($event_share))->handle();

        $guest_names = $result->event_shifts->first()->event_guests->pluck('name')->all();

        $this->assertSame(['Early Guest', 'Late Guest'], $guest_names);
    }
}
