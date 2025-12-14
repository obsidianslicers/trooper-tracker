<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Trait containing local scopes for the Costume model.
 */
trait HasOrganizationCostumeScopes
{
    public function scopeForEvent(Builder $query, Event $event, Trooper $trooper): Builder
    {
        return $query->whereHas('organization.event_organizations', function ($q) use ($event)
        {
            $q->where(EventOrganization::EVENT_ID, $event->id)
                ->where(EventOrganization::CAN_ATTEND, true);
        })->whereHas('trooper_costumes', function ($q) use ($trooper)
        {
            $q->where(TrooperCostume::TROOPER_ID, $trooper->id);
        });

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