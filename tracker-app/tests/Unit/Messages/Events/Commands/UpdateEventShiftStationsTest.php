<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Messages\Events\Commands\UpdateEventShiftStations;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateEventShiftStationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_updates_existing_and_appends_new_stations(): void
    {
        $shift = EventShift::factory()->create();
        $station = EventShiftStation::factory()->forEventShift($shift)
            ->state([EventShiftStation::SEQUENCE => 10, EventShiftStation::TROOPERS_ALLOWED => 2])
            ->create();

        (new UpdateEventShiftStations($shift, [
            $station->id => ['name' => ' Gate ', 'troopers_allowed' => '4'],
            -1 => ['name' => 'Stage', 'troopers_allowed' => 3],
        ]))->handle();

        $station->refresh();
        $this->assertSame('Gate', $station->name);
        $this->assertSame(4, $station->troopers_allowed);
        $this->assertSame(10, $station->sequence);
        $this->assertDatabaseHas('tt_event_shift_stations', [
            EventShiftStation::EVENT_SHIFT_ID => $shift->id,
            EventShiftStation::NAME => 'Stage',
            EventShiftStation::SEQUENCE => 20,
        ]);
    }

    public function test_handle_skips_blank_rows_and_stations_from_other_shifts(): void
    {
        $shift = EventShift::factory()->create();
        $foreign = EventShiftStation::factory()->forEventShift(EventShift::factory()->create())
            ->state([EventShiftStation::NAME => 'Theirs', EventShiftStation::TROOPERS_ALLOWED => 2])
            ->create();

        (new UpdateEventShiftStations($shift, [
            -1 => ['name' => '', 'troopers_allowed' => null],
            $foreign->id => ['name' => 'Mine', 'troopers_allowed' => 5],
        ]))->handle();

        $this->assertSame('Theirs', $foreign->fresh()->name);
        $this->assertSame(0, EventShiftStation::where(EventShiftStation::EVENT_SHIFT_ID, $shift->id)->count());
    }
}
