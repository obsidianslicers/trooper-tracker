<?php

declare(strict_types=1);

namespace App\Messages\Reports\Resources;

use App\Messages\Concerns\FormatsReportNumbers;
use App\Models\Event;
use App\Models\EventShift;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * One event on the Donation Event Summary report: what each shift raised, and the event's total
 * when it had more than one shift.
 *
 * @property Event $resource
 */
class DonationEventRowResource extends JsonResource
{
    use FormatsReportNumbers;

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

    /**
     * The page's totals across every event on it.
     *
     * @param  Collection<int, Event>  $events
     * @return array<string, string>
     */
    public static function grandTotals(Collection $events): array
    {
        $shifts = $events->flatMap(fn (Event $event) => $event->event_shifts);

        return (new self(new Event))->amounts($shifts, true);
    }

    /**
     * @param  Collection<int, EventShift>  $shifts
     * @return array<string, string>
     */
    private function amounts(Collection $shifts, bool $cents): array
    {
        $direct = $shifts->sum('charity_direct_funds');
        $indirect = $shifts->sum('charity_indirect_funds');
        $money = fn ($value): string => $cents ? $this->funds($value) : $this->count($value);

        return [
            'direct' => $money($direct),
            'indirect' => $money($indirect),
            'total' => $money($direct + $indirect),
            'hours' => $this->count($shifts->sum(fn (EventShift $s) => $s->effective_charity_hours)),
            'attendees' => $this->count($shifts->sum('attendees_count')),
            'trooper_hours' => $this->count($shifts->sum(
                fn (EventShift $s) => $s->effective_charity_hours * $s->attendees_count
            )),
        ];
    }
}
