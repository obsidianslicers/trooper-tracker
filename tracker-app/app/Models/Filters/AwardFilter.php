<?php

declare(strict_types=1);

namespace App\Models\Filters;

use App\Models\Award;
use Illuminate\Database\Eloquent\Builder;

/**
 * Applies filters to the Award query based on HTTP request parameters.
 *
 * This class extends the base QueryFilter and defines specific methods for filtering awards
 * by organization.
 */
class AwardFilter extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'organization_id' => 'organization',
            'search_term' => 'searchTerm',
        ];
    }

    /**
     * Filters the query by organization ID.
     *
     * @param Builder $query The Eloquent query builder.
     * @param int|string $value The organization ID from the request.
     * @return Builder The modified query builder.
     */
    protected function organization(Builder $query, $value): Builder
    {
        return $query->where(Award::ORGANIZATION_ID, $value);
    }

    protected function searchTerm(Builder $query, $value): Builder
    {
        if (strlen($value) >= 3)
        {
            return $query->searchFor($value);
        }

        return $query;
    }

    protected function defaults(): array
    {
        return ['scope' => 'active'];
    }
}