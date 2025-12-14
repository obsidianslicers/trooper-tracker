<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\AwardTrooper;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait containing local scopes for the AwardTrooper model.
 *
 * This trait provides query scopes for filtering and retrieving award-trooper
 * relationships, primarily used for displaying a trooper's award history.
 */
trait HasAwardTrooperScopes
{
    /**
     * Scope a query to only include awards for a specific trooper.
     *
     * Eager loads the award relationship and orders by creation date (most recent first)
     * for displaying a trooper's award history.
     *
     * @param Builder<self> $query The Eloquent query builder.
     * @param int $trooper_id The ID of the trooper to filter by.
     * @return Builder<self>
     */
    public function scopeByTrooper(Builder $query, int $trooper_id): Builder
    {
        return $query->with('award:id,name')
            ->where(self::TROOPER_ID, $trooper_id);
    }
}