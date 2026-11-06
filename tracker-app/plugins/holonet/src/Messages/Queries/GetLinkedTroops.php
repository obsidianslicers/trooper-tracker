<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Messages\Queries;

use App\Enums\EventTrooperStatus;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;
use TroopTracker\Plugins\Holonet\Models\LinkedTroop;
use TroopTracker\Plugins\Holonet\Models\TrackerLink;

/**
 * A trooper's troops from every tracker they linked, newest first. A troop that
 * overlaps one recorded here, or one already taken from another linked tracker,
 * is shown once: a convention several garrisons covered counts one time.
 *
 * @method static Collection<int, LinkedTroop> call(Trooper $trooper)
 */
final class GetLinkedTroops extends Message
{
    public function __construct(private readonly Trooper $trooper) {}

    /**
     * @return Collection<int, LinkedTroop>
     */
    public function handle(): Collection
    {
        $troops = LinkedTroop::query()
            ->whereHas('link', fn ($query) => $query
                ->where(TrackerLink::TROOPER_ID, $this->trooper->id))
            ->with('link')
            ->orderBy(LinkedTroop::SHIFT_STARTS_AT)
            ->orderBy(LinkedTroop::ID)
            ->get();

        if ($troops->isEmpty())
        {
            return collect();
        }

        $kept = collect();
        $local = $this->localShifts();

        foreach ($troops as $troop)
        {
            $overlaps = fn (EventShift|LinkedTroop $other): bool => $troop->overlaps(
                $other->shift_starts_at,
                $other->shift_ends_at,
            );

            if (!$local->contains($overlaps) && !$kept->contains($overlaps))
            {
                $kept->push($troop);
            }
        }

        return $kept->reverse()->values();
    }

    /**
     * @return Collection<int, EventShift>
     */
    private function localShifts(): Collection
    {
        return EventShift::query()
            ->whereIn(EventShift::ID, EventTrooper::query()
                ->select(EventTrooper::EVENT_SHIFT_ID)
                ->where(EventTrooper::TROOPER_ID, $this->trooper->id)
                ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value))
            ->get([EventShift::ID, EventShift::SHIFT_STARTS_AT, EventShift::SHIFT_ENDS_AT]);
    }
}
