<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Support;

use App\Models\Trooper;
use Illuminate\Support\Collection;
use TroopTracker\Plugins\Holonet\Messages\Queries\GetLinkedTroops;
use TroopTracker\Plugins\Holonet\Models\LinkedTroop;

/**
 * One lookup of a trooper's linked troops per request, shared by the service
 * record's summary and history.
 */
class LinkedTroopsOnPage
{
    /** @var array<int, Collection<int, LinkedTroop>> */
    private array $troops = [];

    /**
     * @return Collection<int, LinkedTroop>
     */
    public function for(Trooper $trooper): Collection
    {
        return $this->troops[$trooper->id] ??= GetLinkedTroops::call(trooper: $trooper);
    }

    /**
     * @param  Collection<int, LinkedTroop>  $troops
     */
    public static function hours(Collection $troops): float
    {
        return round($troops->sum(fn (LinkedTroop $troop): float => $troop->hours()), 1);
    }
}
