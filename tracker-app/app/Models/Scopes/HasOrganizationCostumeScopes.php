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
     * - Costumes that the trooper owns (has in trooper_costumes)
     * - Costumes belonging to the specified organizations
     *
     * @param Builder<self> $query The Eloquent query builder
     * @param EventShift $event_shift The event shift to check availability for
     * @param Trooper $trooper The trooper whose costumes to retrieve
     * @param Collection|array<int> $organization_ids The organization IDs to filter costumes by
     * @return Builder<self>
     */
    public function scopeForEventShift(Builder $query, EventShift $event_shift, Trooper $trooper, Collection|array $organization_ids): Builder
    {
        return $query->whereHas('trooper_costumes', function ($q) use ($trooper)
        {
            $q->where(TrooperCostume::TROOPER_ID, $trooper->id);
        })->whereIn(self::ORGANIZATION_ID, $organization_ids);
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