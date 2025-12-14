<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Base\EventTrooper;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Trait containing local scopes for the Event model.
 */
trait HasEventScopes
{
    /**
     * Scope a query to only include active events.
     *
     * Active events are those with OPEN, DRAFT, or SIGN_UP_LOCKED status
     * that have ended before the current time, ordered by start date.
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

        return $query->whereIn(self::STATUS, $status_list)
            ->where(self::EVENT_END, '<', now())
            ->orderBy(self::EVENT_START);
    }

    /**
     * Scope a query to only include upcoming events.
     *
     * Upcoming events are those with OPEN or SIGN_UP_LOCKED status
     * that start on or after the current time, ordered by start date.
     *
     * @param Builder<self> $query The Eloquent query builder.
     * @return Builder<self>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        $status_list = [
            EventStatus::OPEN,
            EventStatus::SIGN_UP_LOCKED,
        ];

        return $query->whereIn(self::STATUS, $status_list)
            ->where(self::EVENT_START, '>=', now())
            ->orderBy(self::EVENT_START);
    }

    /**
     * Scope a query to eager load event shifts with trooper counts.
     *
     * Eager loads the event_shifts relationship, ordered by shift start time,
     * and includes a count of event_troopers with GOING status.
     *
     * @param Builder<self> $query The Eloquent query builder.
     * @return Builder<self>
     */
    public function scopeWithShifts(Builder $query): Builder
    {
        return $query->with(['event_shifts' => function ($q)
        {
            $q->orderBy(EventShift::SHIFT_STARTS_AT)->withCount([
                'event_troopers as event_troopers_count' => function ($qx)
                {
                    $qx->where(EventTrooper::IS_HANDLER, false)
                        ->where(EventTrooper::STATUS, EventTrooperStatus::GOING);
                }
            ]);
        }]);
    }

    // /**
    //  * Scope a query to find events a specific trooper is signed up for.
    //  *
    //  * This scope filters events based on a trooper's participation and whether the
    //  * event is open or closed. It also eagerly loads the relevant relationships
    //  * for displaying the event details for that trooper.
    //  *
    //  * @param Builder<self> $query The Eloquent query builder.
    //  * @param int $trooper_id The ID of the trooper to filter by.
    //  * @param bool $closed True to fetch closed (historical) events, false for open events.
    //  * @return Builder<self>
    //  */
    // public function scopeByTrooper(Builder $query, int $trooper_id, bool $closed): Builder
    // {
    //     $with = [
    //         'event_troopers' => function ($q) use ($trooper_id)
    //         {
    //             $q->where(EventTrooper::TROOPER_ID, $trooper_id)
    //                 ->with('costume.organization');
    //         },
    //     ];

    //     return $query->with($with)
    //         ->where(self::STATUS, $closed ? EventStatus::CLOSED : EventStatus::OPEN)
    //         ->whereHas('event_troopers', function ($q) use ($trooper_id)
    //         {
    //             $q->where(EventTrooper::TROOPER_ID, $trooper_id);
    //         });
    // }

    /**
     * Scope a query to only include events that can be managed by a given moderator.
     *
     * An event is moderated if its organization falls within the moderator's
     * assigned organizational hierarchy.
     *
     * @param Builder<self> $query The Eloquent query builder.
     * @param Trooper $trooper The moderator to filter by.
     * @return Builder<self>
     */
    public function scopeModeratedBy(Builder $query, Trooper $trooper): Builder
    {
        if ($trooper->isAdministrator())
        {
            return $query;
        }

        return $query->whereExists(function ($sub) use ($trooper)
        {
            $sub->select(DB::raw(1))
                ->from('tt_trooper_assignments as ta_moderator')
                ->join('tt_organizations as org_moderator', 'ta_moderator.organization_id', '=', 'org_moderator.id')
                ->join('tt_organizations as org_event', 'tt_events.organization_id', '=', 'org_event.id')
                ->where('ta_moderator.trooper_id', $trooper->id)
                ->where('ta_moderator.is_moderator', true)
                ->whereRaw('org_event.node_path LIKE CONCAT(org_moderator.node_path, "%")');
        });
    }

    /**
     * Scope a query to search for events by a given search term.
     *
     * Automatically adds wildcard characters to the beginning and end of the
     * search term if not already present, then searches the event name.
     *
     * @param Builder<self> $query The Eloquent query builder.
     * @param string $search_term The term to search for in the event name field.
     * @return Builder<self>
     */
    public function scopeSearchFor(Builder $query, string $search_term): Builder
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