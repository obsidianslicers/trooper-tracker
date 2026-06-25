<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

/**
 * Provides query filtering functionality for models.
 *
 * This trait adds the filterWith() scope to models, allowing them to apply
 * QueryFilter classes for complex query filtering operations.
 */
trait HasFilter
{
    /**
     * Apply a filter class to the query.
     *
     * @param  Builder<self>  $query  The Eloquent query builder.
     * @param  QueryFilter  $filter  The filter instance to apply to the query.
     * @return Builder<self> The filtered query builder.
     */
    public function scopeFilterWith(Builder $query, QueryFilter $filter): Builder
    {
        return $filter->apply($query);
    }
}
