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
        if (!$this->canGoAtStation($event_shift, $event_shift_station_id, lock: $lock))
        {
            return false;
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
     * Decide whether a trooper may hold a GOING spot at the given station.
     *
     * Non-stationed shifts always have room. On a stationed shift a trooper
     * must occupy a station, and that station must have an open slot
     * (optionally ignoring the trooper's own current spot).
     */
    public function canGoAtStation(
        EventShift $event_shift,
        ?int $event_shift_station_id,
        ?int $excluding_event_trooper_id = null,
        bool $lock = false,
    ): bool {
        if (!$event_shift->usesStations())
        {
            return true;
        }

        if ($event_shift_station_id === null)
        {
            return false;
        }

        return !$event_shift->stationMaxed(
            $event_shift_station_id,
            $excluding_event_trooper_id,
            $lock,
        );
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
