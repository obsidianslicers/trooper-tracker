<?php

declare(strict_types=1);

namespace App\Messages\Events\Queries;

use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * The trooper's own sign-up status on each of the given shifts, for marking the troops
 * they are already part of. Shifts they never signed up for, or dropped out of, are left out.
 *
 * @method static Collection<int, EventTrooperStatus> call(Trooper $trooper, array $event_shift_ids)
 */
final class GetTrooperSignUpStatuses extends Message
{
    private const SIGNED_UP = [
        EventTrooperStatus::GOING,
        EventTrooperStatus::STAND_BY,
        EventTrooperStatus::TENTATIVE,
        EventTrooperStatus::PENDING,
    ];

    /**
     * @param  array<int, int>  $event_shift_ids
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly array $event_shift_ids,
    ) {}

    /**
     * @return Collection<int, EventTrooperStatus> Keyed by event shift ID
     */
    public function handle(): Collection
    {
        if (empty($this->event_shift_ids))
        {
            return collect();
        }

        return EventTrooper::query()
            ->where(EventTrooper::TROOPER_ID, $this->trooper->id)
            ->whereIn(EventTrooper::EVENT_SHIFT_ID, $this->event_shift_ids)
            ->whereIn(EventTrooper::STATUS, self::SIGNED_UP)
            ->get([EventTrooper::EVENT_SHIFT_ID, EventTrooper::STATUS])
            ->mapWithKeys(fn (EventTrooper $row) => [$row->event_shift_id => $row->status]);
    }
}
