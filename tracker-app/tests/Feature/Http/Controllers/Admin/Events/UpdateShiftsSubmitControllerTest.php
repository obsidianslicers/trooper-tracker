<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateShiftsSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_shifts_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($trooper)->post('/admin/events/' . $event->id . '/shifts', [
            'shifts' => [
                0 => [
                    'date' => now()->toDateString(),
                    'starts_at' => '10:00',
                    'ends_at' => '12:00',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.events.shifts', ['event' => $event->id]));
    }

    public function test_invoke_updates_parent_event_date_range_from_shifts(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()
            ->withEventStart(Carbon::parse('2026-05-01 10:00:00'))
            ->withEventEnd(Carbon::parse('2026-05-01 12:00:00'))
            ->create();
        $early_shift = EventShift::factory()->forEvent($event)->create();
        $late_shift = EventShift::factory()->forEvent($event)->create();

        $response = $this->actingAs($trooper)->post('/admin/events/' . $event->id . '/shifts', [
            'shifts' => [
                $early_shift->id => [
                    'date' => '2026-06-03',
                    'starts_at' => '10:00',
                    'ends_at' => '12:00',
                ],
                $late_shift->id => [
                    'date' => '2026-06-05',
                    'starts_at' => '09:00',
                    'ends_at' => '17:30',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.events.shifts', ['event' => $event->id]));

        $event->refresh();

        $this->assertSame('2026-06-03 10:00:00', $event->event_start->toDateTimeString());
        $this->assertSame('2026-06-05 17:30:00', $event->event_end->toDateTimeString());
    }

    public function test_invoke_creates_and_updates_shift_stations(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $station = EventShiftStation::factory()
            ->forEventShift($shift)
            ->withName('Booth')
            ->withTroopersAllowed(2)
            ->create();

        $response = $this->actingAs($trooper)->post('/admin/events/' . $event->id . '/shifts', [
            'shifts' => [
                $shift->id => [
                    'date' => '2026-06-03',
                    'starts_at' => '10:00',
                    'ends_at' => '12:00',
                    'stations' => [
                        $station->id => [
                            'name' => 'Info Booth',
                            'troopers_allowed' => 3,
                            'sequence' => 10,
                        ],
                        -1 => [
                            'name' => 'Floor',
                            'troopers_allowed' => 4,
                            'sequence' => 20,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.events.shifts', ['event' => $event->id]));

        $this->assertDatabaseHas('tt_event_shift_stations', [
            EventShiftStation::ID => $station->id,
            EventShiftStation::NAME => 'Info Booth',
            EventShiftStation::TROOPERS_ALLOWED => 3,
            EventShiftStation::SEQUENCE => 10,
        ]);
        $this->assertDatabaseHas('tt_event_shift_stations', [
            EventShiftStation::EVENT_SHIFT_ID => $shift->id,
            EventShiftStation::NAME => 'Floor',
            EventShiftStation::TROOPERS_ALLOWED => 4,
            EventShiftStation::SEQUENCE => 20,
        ]);
    }

    public function test_invoke_creates_multiple_new_shift_stations_at_once(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $response = $this->actingAs($trooper)->post('/admin/events/' . $event->id . '/shifts', [
            'shifts' => [
                $shift->id => [
                    'date' => '2026-06-03',
                    'starts_at' => '10:00',
                    'ends_at' => '12:00',
                    'stations' => [
                        -1 => [
                            'name' => 'Booth',
                            'troopers_allowed' => 2,
                        ],
                        -2 => [
                            'name' => 'Floor',
                            'troopers_allowed' => 4,
                        ],
                        -3 => [
                            'name' => 'Check-in',
                            'troopers_allowed' => 1,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.events.shifts', ['event' => $event->id]));

        $this->assertDatabaseHas('tt_event_shift_stations', [
            EventShiftStation::EVENT_SHIFT_ID => $shift->id,
            EventShiftStation::NAME => 'Booth',
            EventShiftStation::TROOPERS_ALLOWED => 2,
        ]);
        $this->assertDatabaseHas('tt_event_shift_stations', [
            EventShiftStation::EVENT_SHIFT_ID => $shift->id,
            EventShiftStation::NAME => 'Floor',
            EventShiftStation::TROOPERS_ALLOWED => 4,
        ]);
        $this->assertDatabaseHas('tt_event_shift_stations', [
            EventShiftStation::EVENT_SHIFT_ID => $shift->id,
            EventShiftStation::NAME => 'Check-in',
            EventShiftStation::TROOPERS_ALLOWED => 1,
        ]);
    }

    public function test_invoke_promotes_station_standbys_when_station_capacity_has_room(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $station = EventShiftStation::factory()
            ->forEventShift($shift)
            ->withName('Docking Bay')
            ->withTroopersAllowed(1)
            ->create();
        EventTrooper::factory()
            ->forEventShiftStation($station)
            ->asGoing()
            ->create();
        $standby = EventTrooper::factory()
            ->forEventShiftStation($station)
            ->create([EventTrooper::STATUS => EventTrooperStatus::STAND_BY]);

        $response = $this->actingAs($trooper)->post('/admin/events/' . $event->id . '/shifts', [
            'shifts' => [
                $shift->id => [
                    'date' => '2026-06-03',
                    'starts_at' => '10:00',
                    'ends_at' => '12:00',
                    'stations' => [
                        $station->id => [
                            'name' => 'Docking Bay',
                            'troopers_allowed' => 2,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.events.shifts', ['event' => $event->id]));

        $this->assertSame(EventTrooperStatus::GOING, $standby->fresh()->status);
    }

    public function test_invoke_demotes_latest_station_going_troopers_when_station_capacity_is_lowered(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $station = EventShiftStation::factory()
            ->forEventShift($shift)
            ->withName('Docking Bay')
            ->withTroopersAllowed(3)
            ->create();
        $first = EventTrooper::factory()
            ->forEventShiftStation($station)
            ->asGoing()
            ->withSignedUpAt(Carbon::parse('2026-06-01 10:00:00'))
            ->create();
        $second = EventTrooper::factory()
            ->forEventShiftStation($station)
            ->asGoing()
            ->withSignedUpAt(Carbon::parse('2026-06-01 10:01:00'))
            ->create();
        $latest = EventTrooper::factory()
            ->forEventShiftStation($station)
            ->asGoing()
            ->withSignedUpAt(Carbon::parse('2026-06-01 10:02:00'))
            ->create();

        $response = $this->actingAs($trooper)->post('/admin/events/' . $event->id . '/shifts', [
            'shifts' => [
                $shift->id => [
                    'date' => '2026-06-03',
                    'starts_at' => '10:00',
                    'ends_at' => '12:00',
                    'stations' => [
                        $station->id => [
                            'name' => 'Docking Bay',
                            'troopers_allowed' => 2,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.events.shifts', ['event' => $event->id]));

        $this->assertSame(EventTrooperStatus::GOING, $first->fresh()->status);
        $this->assertSame(EventTrooperStatus::GOING, $second->fresh()->status);
        $this->assertSame(EventTrooperStatus::STAND_BY, $latest->fresh()->status);
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->post('/admin/events/' . $event->id . '/shifts', []);

        $response->assertRedirect(route('auth.login'));
    }
}
