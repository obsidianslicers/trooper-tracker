<?php

declare(strict_types=1);

namespace App\Models\Filters;

use App\Models\Award;
use Illuminate\Database\Eloquent\Builder;

/**
 * Applies filters to the Award query based on HTTP request parameters.
 *
 * This class extends the base QueryFilter and defines specific methods for filtering notices
 * by scope (active, past, future) and organization.
 */
class AwardFilter extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'organization_id' => 'organization',
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

    protected function defaults(): array
    {
        return ['scope' => 'active'];
    }
}