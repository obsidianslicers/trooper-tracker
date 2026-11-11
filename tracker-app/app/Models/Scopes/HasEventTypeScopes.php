<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\EventType;
use Illuminate\Database\Eloquent\Builder;

/**
 * Local scopes for the EventType model.
 */
trait HasEventTypeScopes
{
    /**
     * Types in the order administrators arranged them.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy(EventType::SORT_ORDER)->orderBy(EventType::NAME);
    }

    /**
     * Types offered on the event form.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(EventType::IS_ACTIVE, true);
    }

    /**
     * Ids of the types whose events don't count toward troop totals, as a subquery.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUncountedIds(Builder $query): Builder
    {
        return $query->select(EventType::ID)->where(EventType::IS_COUNTED, false);
    }
}
