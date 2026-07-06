<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemoveShiftStationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_removes_station_without_signups(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $station = EventShiftStation::factory()->forEventShift($shift)->create();

        $response = $this->actingAs($trooper)->post(
            route('admin.events.shifts.stations.remove', [
                'event' => $event,
                'event_shift' => $shift,
                'event_shift_station' => $station,
            ])
        );

        $response->assertNoContent();
        $response->assertHeader('HX-Redirect', route('admin.events.shifts', compact('event')));
        $this->assertSoftDeleted('tt_event_shift_stations', [
            EventShiftStation::ID => $station->id,
        ]);
    }

    public function test_invoke_does_not_remove_station_with_signups(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $station = EventShiftStation::factory()->forEventShift($shift)->create();
        EventTrooper::factory()->forEventShiftStation($station)->create();

        $this->actingAs($trooper)->post(
            route('admin.events.shifts.stations.remove', [
                'event' => $event,
                'event_shift' => $shift,
                'event_shift_station' => $station,
            ])
        )->assertNoContent();

        $this->assertDatabaseHas('tt_event_shift_stations', [
            EventShiftStation::ID => $station->id,
            EventShiftStation::DELETED_AT => null,
        ]);
    }

    public function test_invoke_returns_404_for_station_from_different_shift(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $other_shift = EventShift::factory()->forEvent($event)->create();
        $station = EventShiftStation::factory()->forEventShift($other_shift)->create();

        $this->actingAs($trooper)->post(
            route('admin.events.shifts.stations.remove', [
                'event' => $event,
                'event_shift' => $shift,
                'event_shift_station' => $station,
            ])
        )->assertNotFound();
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $station = EventShiftStation::factory()->forEventShift($shift)->create();

        $this->post(
            route('admin.events.shifts.stations.remove', [
                'event' => $event,
                'event_shift' => $shift,
                'event_shift_station' => $station,
            ])
        )->assertRedirect(route('auth.login'));
    }
}
