<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReorderShiftStationsSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_station_sequence_for_given_shift(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $station_a = EventShiftStation::factory()->forEventShift($shift)->create([EventShiftStation::SEQUENCE => 10]);
        $station_b = EventShiftStation::factory()->forEventShift($shift)->create([EventShiftStation::SEQUENCE => 20]);
        $station_c = EventShiftStation::factory()->forEventShift($shift)->create([EventShiftStation::SEQUENCE => 30]);

        $response = $this->actingAs($trooper)->postJson(
            route('admin.events.shifts.stations.reorder', ['event' => $event, 'event_shift' => $shift]),
            ['ids' => [$station_c->id, $station_a->id, $station_b->id]]
        );

        $response->assertOk();
        $this->assertDatabaseHas('tt_event_shift_stations', [EventShiftStation::ID => $station_c->id, EventShiftStation::SEQUENCE => 10]);
        $this->assertDatabaseHas('tt_event_shift_stations', [EventShiftStation::ID => $station_a->id, EventShiftStation::SEQUENCE => 20]);
        $this->assertDatabaseHas('tt_event_shift_stations', [EventShiftStation::ID => $station_b->id, EventShiftStation::SEQUENCE => 30]);
    }

    public function test_invoke_ignores_station_from_another_shift(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $other_shift = EventShift::factory()->forEvent($event)->create();
        $station = EventShiftStation::factory()->forEventShift($shift)->create([EventShiftStation::SEQUENCE => 10]);
        $other_station = EventShiftStation::factory()->forEventShift($other_shift)->create([EventShiftStation::SEQUENCE => 50]);

        $this->actingAs($trooper)->postJson(
            route('admin.events.shifts.stations.reorder', ['event' => $event, 'event_shift' => $shift]),
            ['ids' => [$other_station->id, $station->id]]
        )->assertOk();

        $this->assertDatabaseHas('tt_event_shift_stations', [EventShiftStation::ID => $station->id, EventShiftStation::SEQUENCE => 20]);
        $this->assertDatabaseHas('tt_event_shift_stations', [EventShiftStation::ID => $other_station->id, EventShiftStation::SEQUENCE => 50]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $response = $this->postJson(
            route('admin.events.shifts.stations.reorder', ['event' => $event, 'event_shift' => $shift]),
            ['ids' => []]
        );

        $response->assertUnauthorized();
    }
}
