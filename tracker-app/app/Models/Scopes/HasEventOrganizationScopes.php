<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\EventShift;
use App\Models\EventTrooper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Trait containing local scopes for the EventOrganization model.
 */
trait HasEventOrganizationScopes
{
    /**
     * Scope a query to get organization IDs that can attend an event shift.
     *
     * This scope filters organizations based on:
     * - Organizations that can attend the event (can_attend = true)
     * - Organizations that have not reached their trooper limit for the shift
     *
     * Returns a collection of organization IDs where members are allowed to sign up
     * for the specified event shift, taking into account both the can_attend flag
     * and any trooper limits that may be set.
     *
     * @param Builder<self> $query The Eloquent query builder
     * @param EventShift $event_shift The event shift to check availability for
     * @return Collection<int, int> Collection of organization IDs that can attend
     */
    public function scopePluckCanAttend(Builder $query, EventShift $event_shift): Collection
    {
        return $query->where(self::CAN_ATTEND, true)
            ->withCount(['troopers as troopers_count' => function ($q) use ($event_shift): void
            {
                $q->where(EventTrooper::EVENT_SHIFT_ID, $event_shift->id);
            }])
            ->get()
            ->filter(fn($e_org) => $e_org->troopers_allowed === null || $e_org->troopers_count < $e_org->troopers_allowed)
            ->pluck('organization_id');
    }
}

