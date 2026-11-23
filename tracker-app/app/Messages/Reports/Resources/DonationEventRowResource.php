<?php

declare(strict_types=1);

namespace App\Messages\Reports\Resources;

use App\Messages\Concerns\SumsDonationAmounts;
use App\Models\Event;
use App\Models\EventShift;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One event on the Donation Event Summary report: what each shift raised, and the event's total
 * when it had more than one shift.
 *
 * @property Event $resource
 */
class DonationEventRowResource extends JsonResource
{
    use SumsDonationAmounts;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event = $this->resource;
        $shifts = $event->event_shifts;

        return [
            'id' => $event->id,
            'name' => $event->name,
            'url' => route('admin.events.edit', ['event' => $event]),
            'organization_name' => $event->organization?->name,
            'date' => $event->event_start?->localFormat('M d, Y'),
            'shifts' => $shifts->map(fn (EventShift $shift): array => [
                'id' => $shift->id,
                'time' => $shift->compact_time_display,
                'charity_name' => $shift->charity_name,
                'charity_notes' => $shift->charity_notes,
                ...$this->amounts(collect([$shift]), true),
            ])->values()->all(),
            'totals' => $shifts->count() > 1 ? $this->amounts($shifts, false) : null,
        ];
    }
}
