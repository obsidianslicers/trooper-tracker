<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\EventStatus;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Trait containing local scopes for the Event model.
 */
trait HasEventScopes
{
    /**
     * Scope a query to find events a specific trooper is signed up for.
     *
     * This scope filters events based on a trooper's participation and whether the
     * event is open or closed. It also eagerly loads the relevant relationships
     * for displaying the event details for that trooper.
     *
     * @param Builder<self> $query The Eloquent query builder.
     * @param int $trooper_id The ID of the trooper to filter by.
     * @param bool $closed True to fetch closed (historical) events, false for open events.
     * @return Builder<self>
     */
    protected function scopeByTrooper(Builder $query, int $trooper_id, bool $closed): Builder
    {
        $with = [
            'event_troopers' => function ($q) use ($trooper_id)
            {
                $q->where(EventTrooper::TROOPER_ID, $trooper_id)
                    ->with('costume.organization');
            },
        ];

        return $query->with($with)
            ->where(self::STATUS, $closed ? EventStatus::CLOSED : EventStatus::OPEN)
            ->whereHas('event_troopers', function ($q) use ($trooper_id)
            {
                $q->where(EventTrooper::TROOPER_ID, $trooper_id);
            });
    }

    /**
     * Scope a query to only include events that can be managed by a given moderator.
     *
     * An event is moderated if its organization falls within the moderator's
     * assigned organizational hierarchy.
     *
     * @param Builder<self> $query The Eloquent query builder.
     * @param Trooper $moderator The moderator to filter by.
     * @return Builder<self>
     */
    protected function scopeModeratedBy(Builder $query, Trooper $moderator): Builder
    {
        return $query->whereExists(function ($sub) use ($moderator)
        {
            $sub->select(DB::raw(1))
                ->from('tt_trooper_assignments as ta_moderator')
                ->join('tt_organizations as org_moderator', 'ta_moderator.organization_id', '=', 'org_moderator.id')
                ->join('tt_organizations as org_event', 'tt_events.organization_id', '=', 'org_event.id')
                ->where('ta_moderator.trooper_id', $moderator->id)
                ->where('ta_moderator.moderator', true)
                ->whereRaw('org_event.node_path LIKE CONCAT(org_moderator.node_path, "%")');
        });
    }

    /**
     * Scope a query to search for troopers by a given search term.
     *
     * @param Builder<self> $query The Eloquent query builder.
     * @param string $search_term The term to search for in name, username, and email fields.
     * @return Builder<self>
     */
    protected function scopeSearchFor(Builder $query, string $search_term): Builder
    {
        if (!str_starts_with($search_term, '%'))
        {
            $search_term = '%' . $search_term;
        }

        if (!str_ends_with($search_term, '%'))
        {
            $search_term .= '%';
        }

        return $query->where(self::NAME, 'like', $search_term);
    }
}