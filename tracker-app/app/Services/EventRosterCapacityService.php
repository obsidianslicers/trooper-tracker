<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EventShift;

/**
 * Single source of truth for roster capacity decisions.
 *
 * A trooper may hold a GOING spot only when every applicable limit has room:
 * the event-wide limit (null = unlimited), the organization limit
 * (null = unlimited), and — for stationed shifts — the station limit,
 * which is always a required positive number and is never unlimited.
 */
readonly class EventRosterCapacityService
{
    /**
     * Decide whether a trooper can occupy a GOING spot right now.
     *
     * Queries live roster counts. Pass $lock = true inside a transaction to
     * serialize competing signups for the final spot.
     */
    public function canGo(
        EventShift $event_shift,
        ?int $organization_id,
        ?int $event_shift_station_id,
        bool $is_handler,
        bool $lock = false,
    ): bool {
        if ($event_shift->usesStations())
        {
            if ($event_shift_station_id === null)
            {
                return false;
            }

            if ($event_shift->stationMaxed($event_shift_station_id, lock: $lock))
            {
                return false;
            }
        }

        $event_full = $is_handler
            ? $event_shift->handlersMaxed(lock: $lock)
            : $event_shift->troopersMaxed(lock: $lock);

        if ($event_full)
        {
            return false;
        }

        if ($organization_id !== null
            && $event_shift->orgTroopersMaxed($organization_id, $is_handler, lock: $lock))
        {
            return false;
        }

        return true;
    }

    /**
     * Event-wide and organization limits: null means unlimited.
     */
    public function limitHasRoom(?int $limit, int $going_count): bool
    {
        return $limit === null || $going_count < $limit;
    }

    /**
     * Station limits are always required; a missing limit means no capacity.
     */
    public function stationHasRoom(?int $station_limit, int $going_count): bool
    {
        return $station_limit !== null && $going_count < $station_limit;
    }
}
