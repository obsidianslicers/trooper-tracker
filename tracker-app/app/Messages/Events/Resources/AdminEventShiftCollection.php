<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources;

use App\Models\EventShift;
use App\Models\EventShiftStation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * The event's shifts and their stations for the Shifts tab.
 */
class AdminEventShiftCollection extends ResourceCollection
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn (EventShift $shift): array => [
                'id' => $shift->id,
                'day' => $shift->shift_starts_at->localFormat('D'),
                'date' => $shift->shift_starts_at->format('Y-m-d'),
                'starts_at' => $shift->shift_starts_at->format('H:i'),
                'ends_at' => $shift->shift_ends_at->format('H:i'),
                'status' => $shift->status->value,
                'stations' => $this->stations($shift),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string,troopers_allowed:int}>
     */
    private function stations(EventShift $shift): array
    {
        return $shift->event_shift_stations
            ->sortBy([[EventShiftStation::SEQUENCE, 'asc'], [EventShiftStation::NAME, 'asc']])
            ->map(fn (EventShiftStation $station): array => [
                'id' => $station->id,
                'name' => $station->name,
                'troopers_allowed' => $station->troopers_allowed,
            ])
            ->values()
            ->all();
    }
}
