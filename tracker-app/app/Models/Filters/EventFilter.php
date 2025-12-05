<?php

declare(strict_types=1);

namespace App\Models\Filters;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;

/**
 * Applies filters to the Event query based on HTTP request parameters.
 *
 * This class extends the base QueryFilter and defines specific methods for filtering events
 * by status, organization, and a search term.
 */
class EventFilter extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'status' => 'status',
            'organization_id' => 'organization',
            'search_term' => 'searchTerm',
        ];
    }

    /**
     * Filters the query by event status.
     *
     * @param Builder $query The Eloquent query builder.
     * @param string $value The status value from the request.
     * @return Builder The modified query builder.
     */
    protected function status(Builder $query, $value): Builder
    {
        $status = EventStatus::from($value);

        return $query->where(Event::STATUS, $status);
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
        return $query->where(Event::ORGANIZATION_ID, $value);
    }

    /**
     * Applies a search scope to the query based on a search term.
     *
     * The search is only applied if the term is 3 or more characters long.
     *
     * @param Builder $query The Eloquent query builder.
     * @param string $value The search term from the request.
     * @return Builder The modified query builder.
     */
    protected function searchTerm(Builder $query, $value): Builder
    {
        if (strlen($value) >= 3)
        {
            return $query->searchFor($value);
        }

        return $query;
    }
}