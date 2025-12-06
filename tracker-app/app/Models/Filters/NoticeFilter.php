<?php

declare(strict_types=1);

namespace App\Models\Filters;

use App\Enums\NoticeType;
use App\Models\Notice;
use Illuminate\Database\Eloquent\Builder;

/**
 * Applies filters to the Notice query based on HTTP request parameters.
 *
 * This class extends the base QueryFilter and defines specific methods for filtering notices
 * by scope (active, past, future) and organization.
 */
class NoticeFilter extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'scope' => 'scope',
            'organization_id' => 'organization',
        ];
    }

    /**
     * Filters the query by the notice's scope (active, past, or future).
     *
     * @param Builder $query The Eloquent query builder.
     * @param string $value The scope value from the request.
     * @return Builder The modified query builder.
     */
    protected function scope(Builder $query, $value): Builder
    {
        switch ($value)
        {
            case 'active':
                return $query->active();
            case 'past':
                return $query->past();
            case 'future':
                return $query->future();
        }

        return $query->active();
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
        return $query->where(Notice::ORGANIZATION_ID, $value);
    }

    protected function defaults(): array
    {
        return ['scope' => 'active'];
    }
}