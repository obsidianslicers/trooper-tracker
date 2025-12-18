<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Trait containing local scopes for the Costume model.
 */
trait HasOrganizationCostumeScopes
{
    /**
     * Scope a query to costumes available for a trooper at a specific event shift.
     *
     * This scope filters costumes based on:
     * - Organizations that are allowed to attend the event (can_attend = true)
     * - Organizations that have not reached their trooper limit for the shift
     * - Costumes that the trooper owns (has in trooper_costumes)
     *
     * @param Builder<self> $query The Eloquent query builder
     * @param EventShift $event_shift The event shift to check availability for
     * @param Trooper $trooper The trooper whose costumes to retrieve
     * @return Builder<self>
     */
    public function scopeForEventShift(Builder $query, EventShift $event_shift, Trooper $trooper): Builder
    {
        $organizations_can_attend = $event_shift->event->event_organizations()
            ->where(EventOrganization::CAN_ATTEND, true)
            ->withCount(['troopers as troopers_count' => function ($q) use ($event_shift): void
            {
                $q->where(EventTrooper::EVENT_SHIFT_ID, $event_shift->id);
            }])
            ->get()
            ->filter(fn($e_org) => $e_org->troopers_allowed === null || $e_org->troopers_count < $e_org->troopers_allowed)
            ->pluck('organization_id');

        return $query->whereHas('trooper_costumes', function ($q) use ($trooper)
        {
            $q->where(TrooperCostume::TROOPER_ID, $trooper->id);
        })->whereIn(self::ORGANIZATION_ID, $organizations_can_attend);
    }

    /**
     * Scope a query to exclude a set of costumes by their IDs.
     *
     * @param Builder<self> $query The Eloquent query builder.
     * @param array<int> $costume_ids An array of costume IDs to exclude from the query results.
     * @return Builder<self>
     */
    public function scopeExcluding(Builder $query, Collection|array $costume_ids): Builder
    {
        return $query->whereNotIn(self::ID, $costume_ids);
    }
}