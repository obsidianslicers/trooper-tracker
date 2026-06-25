<?php

declare(strict_types=1);

namespace App\Models\Filters;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\EventOrganization;
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
            'type' => 'type',
            'organization_id' => 'organization',
            'costume_organization_id' => 'costume',
            'search_term' => 'searchTerm',
        ];
    }

    /**
     * Filters the query by event status.
     *
     * @param  Builder  $query  The Eloquent query builder.
     * @param  string  $status  The status value from the request.
     * @return Builder The modified query builder.
     */
    protected function status(Builder $query, $status): Builder
    {
        $status = EventStatus::from($status);

        return $query->where(Event::STATUS, $status);
    }

    /**
     * Filters the query by event type.
     *
     * @param  Builder  $query  The Eloquent query builder.
     * @param  string  $type  The type value from the request.
     * @return Builder The modified query builder.
     */
    protected function type(Builder $query, $type): Builder
    {
        $type = EventType::from($type);

        return $query->where(Event::TYPE, $type);
    }

    /**
     * Filters the query by organization ID.
     *
     * @param  Builder  $query  The Eloquent query builder.
     * @param  int|string  $organization_id  The organization ID from the request.
     * @return Builder The modified query builder.
     */
    protected function organization(Builder $query, $organization_id): Builder
    {
        return $query->where(Event::ORGANIZATION_ID, $organization_id);
    }

    /**
     * Filters the query by organization ID for costume.
     *
     * @param  Builder  $query  The Eloquent query builder.
     * @param  int|string  $organization_id  The organization ID from the request.
     * @return Builder The modified query builder.
     */
    protected function costume(Builder $query, $organization_id): Builder
    {
        return $query->whereHas('organizations', function ($query) use ($organization_id) {
            $query->where(EventOrganization::CAN_ATTEND, true)
                ->where(EventOrganization::ORGANIZATION_ID, $organization_id);
        });
    }

    /**
     * Applies a search scope to the query based on a search term.
     *
     * The search is only applied if the term is 3 or more characters long.
     *
     * @param  Builder  $query  The Eloquent query builder.
     * @param  string  $search_term  The search term from the request.
     * @return Builder The modified query builder.
     */
    protected function searchTerm(Builder $query, $search_term): Builder
    {
        if (strlen($search_term) >= 3)
        {
            return $query->searchFor($search_term);
        }

        return $query;
    }
}
