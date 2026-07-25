<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\EventTrooperStatus;
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
     * @param int|null $excluding_event_trooper_id Event trooper to exclude from the
     *   count, so a trooper already occupying a slot isn't counted against their own room
     * @return Collection<int, int> Collection of organization IDs that can attend
     */
    public function scopePluckCanAttend(Builder $query, EventShift $event_shift, ?int $excluding_event_trooper_id = null): Collection
    {
        return $query->where(self::CAN_ATTEND, true)
            ->get()
            ->filter(function ($e_org) use ($event_shift, $excluding_event_trooper_id)
            {
                if ($e_org->troopers_allowed === null)
                {
                    return true;
                }

                $troopers_count = EventTrooper::query()
                    ->where(EventTrooper::EVENT_SHIFT_ID, $event_shift->id)
                    ->where(EventTrooper::STATUS, EventTrooperStatus::GOING)
                    ->when($excluding_event_trooper_id !== null, fn ($q) => $q->where(EventTrooper::ID, '!=', $excluding_event_trooper_id))
                    ->where(function ($q) use ($e_org)
                    {
                        $q->where(EventTrooper::ORGANIZATION_ID, $e_org->organization_id)
                            ->orWhere(function ($q2) use ($e_org)
                            {
                                $q2->whereNull(EventTrooper::ORGANIZATION_ID)
                                    ->whereJsonContains(EventTrooper::COSTUME_ORGANIZATION_IDS, $e_org->organization_id);
                            });
                    })
                    ->count();

                return $troopers_count < $e_org->troopers_allowed;
            })
            ->pluck('organization_id');
    }
}

