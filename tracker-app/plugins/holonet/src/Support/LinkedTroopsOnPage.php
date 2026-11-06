<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Support;

use App\Models\Trooper;
use Illuminate\Support\Collection;
use TroopTracker\Plugins\Holonet\Messages\Queries\GetLinkedTroops;
use TroopTracker\Plugins\Holonet\Models\HolonetTroop;

/**
 * One lookup of a trooper's linked troops per request, shared by the service
 * record's summary and history.
 */
class LinkedTroopsOnPage
{
    /** @var array<int, Collection<int, HolonetTroop>> */
    private array $troops = [];

    /**
     * @return Collection<int, HolonetTroop>
     */
    public function for(Trooper $trooper): Collection
    {
        return $this->troops[$trooper->id] ??= GetLinkedTroops::call(trooper: $trooper);
    }

    /**
     * @param  Collection<int, HolonetTroop>  $troops
     */
    public static function hours(Collection $troops): float
    {
        return round($troops->sum(fn (HolonetTroop $troop): float => $troop->hours()), 1);
    }
}
