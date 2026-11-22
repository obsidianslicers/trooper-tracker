<?php

declare(strict_types=1);

namespace App\Messages\Events\Queries;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Database\Eloquent\Collection;

/**
 * The trooper's own sign-ups still waiting for a "did you make it?" answer: still Going or
 * Tentative on a shift that has closed and ended, while the event is in its grace period,
 * and not already sent to a moderator for verification. Oldest first, so the troop closest to
 * leaving its grace period comes first.
 *
 * It runs on every page that draws the bell, so the filter is one joined query; the shift and
 * event are loaded only when there are rows.
 *
 * @method static Collection<int, EventTrooper> call(Trooper $trooper)
 */
final class GetTroopsToConfirm extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
    ) {}

    /**
     * @return Collection<int, EventTrooper>
     */
    public function handle(): Collection
    {
        $troopers = (new EventTrooper)->getTable();
        $shifts = (new EventShift)->getTable();
        $events = (new Event)->getTable();

        return EventTrooper::query()
            ->select("{$troopers}.*")
            ->join($shifts, "{$shifts}.".EventShift::ID, '=', "{$troopers}.".EventTrooper::EVENT_SHIFT_ID)
            ->join($events, "{$events}.".Event::ID, '=', "{$shifts}.".EventShift::EVENT_ID)
            ->where("{$troopers}.".EventTrooper::TROOPER_ID, $this->trooper->id)
            ->whereIn("{$troopers}.".EventTrooper::STATUS, EventTrooperStatus::intentToGoArray())
            ->whereNull("{$troopers}.".EventTrooper::REQUESTED_ORGANIZATION_IDS)
            ->where("{$shifts}.".EventShift::STATUS, EventStatus::CLOSED)
            ->where("{$shifts}.".EventShift::SHIFT_ENDS_AT, '<', now())
            ->whereNull("{$shifts}.".EventShift::DELETED_AT)
            //  the grace period, as Event::is_within_grace_period reads it
            ->where("{$events}.".Event::EVENT_END, '<', now())
            ->where("{$events}.".Event::EVENT_END, '>', now()->subDays(30))
            ->whereNull("{$events}.".Event::DELETED_AT)
            ->orderBy("{$shifts}.".EventShift::SHIFT_STARTS_AT)
            ->with([
                'event_shift:'.implode(',', [EventShift::ID, EventShift::EVENT_ID, EventShift::SHIFT_STARTS_AT, EventShift::SHIFT_ENDS_AT]),
                'event_shift.event:'.implode(',', [Event::ID, Event::NAME]),
            ])
            ->get();
    }
}
