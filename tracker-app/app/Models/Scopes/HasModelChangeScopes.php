<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait containing local scopes for the ModelChange model.
 */
trait HasModelChangeScopes
{
    /**
     * Scope a query to only include recent model changes since a given date.
     *
     * Filters ModelChange records to only those created at or after the specified
     * lookback date.
     *
     * @param Builder<self> $query The Eloquent query builder.
     * @param Carbon $lookback The date to filter changes from.
     * @return Builder<self> The filtered query builder.
     */
    public function scopeRecent(Builder $query, Carbon $lookback): Builder
    {
        $query->where(self::CREATED_AT, '>=', $lookback);

        return $query;
    }
}