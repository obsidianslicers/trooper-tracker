<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\EventStatus;
use App\Models\EventTrooper;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait containing local scopes for the EventShift model.
 *
 * This trait provides query scopes for filtering event shifts by various criteria
 * including status, trooper participation, and roster information for event management.
 */
trait HasEventShiftScopes
{
    /**
     * Scope a query to only include active event shifts.
     *
     * Active shifts are those with status of OPEN, DRAFT, or SIGN_UP_LOCKED
     * that haven't ended yet, ordered by their start time.
     *
     * @param Builder<self> $query The Eloquent query builder.
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        $status_list = [
            EventStatus::OPEN,
            EventStatus::DRAFT,
            EventStatus::SIGN_UP_LOCKED,
        ];

        return $query->whereIn(self::STATUS, $status_list);
    }

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
    public function scopeByTrooper(Builder $query, int $trooper_id, bool $closed): Builder
    {
        $with = [
            'event_troopers' => function ($q) use ($trooper_id)
            {
                $q->where(EventTrooper::TROOPER_ID, $trooper_id)
                    ->with('organization_costume.organization');
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
     * Scope a query to load event trooper roster data with relationships.
     *
     * Eager loads trooper, event shift, costume, and organization details,
     * ordered by sign-up timestamp for roster display purposes.
     *
     * @param Builder<self> $query The Eloquent query builder.
     * @return Builder<self> The query with roster relationships and ordering.
     */
    public function scopeRoster(Builder $query): Builder
    {
        $with = [
            'event_troopers.trooper',
            'event_troopers.organization_costume.organization',
            'event_troopers.backup_costume.organization',
        ];

        return $query->with($with)->orderBy(self::SHIFT_STARTS_AT);
    }

    // /**
    //  * Scope a query to only include events that can be managed by a given moderator.
    //  *
    //  * An event is moderated if its organization falls within the moderator's
    //  * assigned organizational hierarchy.
    //  *
    //  * @param Builder<self> $query The Eloquent query builder.
    //  * @param Trooper $moderator The moderator to filter by.
    //  * @return Builder<self>
    //  */
    // public function scopeModeratedBy(Builder $query, Trooper $moderator): Builder
    // {
    // if ($trooper->isAdministrator())
    // {
    //     return $query;
    // }
    //     return $query->whereExists(function ($sub) use ($moderator)
    //     {
    //         $sub->select(DB::raw(1))
    //             ->from('tt_trooper_assignments as ta_moderator')
    //             ->join('tt_organizations as org_moderator', 'ta_moderator.organization_id', '=', 'org_moderator.id')
    //             ->join('tt_organizations as org_event', 'tt_events.organization_id', '=', 'org_event.id')
    //             ->where('ta_moderator.trooper_id', $moderator->id)
    //             ->where('ta_moderator.is_moderator', true)
    //             ->whereRaw('org_event.node_path LIKE CONCAT(org_moderator.node_path, "%")');
    //     });
    // }

    // /**
    //  * Scope a query to search for troopers by a given search term.
    //  *
    //  * @param Builder<self> $query The Eloquent query builder.
    //  * @param string $search_term The term to search for in name, username, and email fields.
    //  * @return Builder<self>
    //  */
    // public function scopeSearchFor(Builder $query, string $search_term): Builder
    // {
    //     if (!str_starts_with($search_term, '%'))
    //     {
    //         $search_term = '%' . $search_term;
    //     }

    //     if (!str_ends_with($search_term, '%'))
    //     {
    //         $search_term .= '%';
    //     }

    //     return $query->where(self::NAME, 'like', $search_term);
    // }

    // /**
    //  * Scope a query for main events
    //  *
    //  * @param Builder<self> $query The Eloquent query builder.
    //  * @return Builder<self>
    //  */
    // public function scopeMainEvents(Builder $query): Builder
    // {
    //     return $query->whereNull(self::MAIN_EVENT_ID);
    // }
}