<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventShiftStation;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;

/**
 * Copies an event as a draft under a new name and start, with its shifts (and their stations)
 * and clubs moved by the same amount of time.
 *
 * @method static Event call(Event $event, string $name, Carbon $event_start)
 */
final class CopyEvent extends Message
{
    use ShouldBeTransactional;

    public function __construct(
        private readonly Event $event,
        private readonly string $name,
        private readonly Carbon $event_start,
    ) {}

    public function handle(): Event
    {
        $diff = $this->event->event_start->diffAsCarbonInterval($this->event_start);

        $copy = $this->event->replicate([
            Event::CREATE_NOTIFICATIONS_SENT_AT,
            Event::CANCEL_NOTIFICATIONS_SENT_AT,
        ]);
        $copy->name = $this->name;
        $copy->event_start = $this->event->event_start->copy()->add($diff);
        $copy->event_end = $this->event->event_end->copy()->add($diff);
        $copy->status = EventStatus::DRAFT;
        $copy->push();

        $this->copyShifts($copy, $diff);

        foreach ($this->event->event_organizations as $organization)
        {
            $organization_copy = $organization->replicate();
            $organization_copy->event_id = $copy->id;
            $organization_copy->save();
        }

        return $copy;
    }

    private function copyShifts(Event $copy, CarbonInterval $diff): void
    {
        $this->event->loadMissing('event_shifts.event_shift_stations');

        foreach ($this->event->event_shifts as $shift)
        {
            $shift_copy = $shift->replicate();
            $shift_copy->event_id = $copy->id;
            $shift_copy->shift_starts_at = $shift->shift_starts_at->copy()->add($diff);
            $shift_copy->shift_ends_at = $shift->shift_ends_at->copy()->add($diff);
            $shift_copy->status = EventStatus::OPEN;
            $shift_copy->save();

            foreach ($shift->event_shift_stations as $station)
            {
                /** @var EventShiftStation $station_copy */
                $station_copy = $station->replicate();
                $station_copy->event_shift_id = $shift_copy->id;
                $station_copy->save();
            }
        }
    }
}
