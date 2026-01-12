<?php

declare(strict_types=1);

namespace App\Services\Admin\Events;

use App\Models\Event;
use App\Models\Filters\EventFilter;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Retrieves paginated events that a trooper has moderation privileges for.
 *
 * This query service filters events based on:
 * - Moderator's organizational hierarchy (via scopeModeratedBy)
 * - Optional EventFilter criteria (status, organization, search term)
 * - Administrators see all events; moderators see only their assigned organizations
 *
 * Results include:
 * - Organization details with trooper's moderator assignment
 * - Event shift counts for each event
 * - Paginated results ordered by event end date (most recent first)
 *
 * This is a **query service** (not a command) - it retrieves data without side effects.
 *
 * Typical usage:
 * - Admin event listing pages
 * - Moderator event management dashboards
 * - Event filtering and search functionality
 */
class GetEventsQuery
{
    /**
     * Execute the query to retrieve events with moderation privileges.
     *
     * Administrators receive all events regardless of organization.
     * Moderators receive only events from organizations they moderate.
     *
     * @param Trooper $trooper The trooper requesting events (admin or moderator).
     * @param EventFilter $filter Optional filter criteria from request parameters.
     * @param int $page_size Number of events per page (default: 15).
     * @return LengthAwarePaginator<Event> Paginated collection of events with query string preserved.
     */
    public function __invoke(Trooper $trooper, EventFilter $filter, int $page_size = 15): LengthAwarePaginator
    {
        $q = Event::with([
            'organization.trooper_assignments' => function ($q) use ($trooper)
            {
                $q->where(TrooperAssignment::TROOPER_ID, $trooper->id)
                    ->where(TrooperAssignment::IS_MODERATOR, true);
            }
        ]);

        $q = $q->withCount('event_shifts');

        $q = $q->filterWith($filter)->moderatedBy($trooper);

        $q->orderByDesc(Event::EVENT_END);

        return $q->paginate($page_size)->withQueryString();
    }
}
