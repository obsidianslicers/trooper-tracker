<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

trait HasFilter
{
    /**
     * Apply a filter class to the query.
     *
     * @param  Builder      $query
     * @param  object       $filter  Must have an apply(Builder $query) method
     * @return Builder
     */
    public function scopeFilterWith(Builder $query, QueryFilter $filter): Builder
    {
        return $filter->apply($query);
    }
}
