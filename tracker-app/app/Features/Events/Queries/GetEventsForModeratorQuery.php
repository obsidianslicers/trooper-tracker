<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Models\Filters\EventFilter;
use App\Models\Trooper;

/**
 * Query to retrieve events for moderator/administrator display.
 *
 * Filters and returns events based on moderator permissions:
 * - Administrators see all events
 * - Moderators see only events from organizations they moderate
 *
 * Supports filtering by status, organization, and search term.
 * Returns paginated results with eager-loaded relationships.
 *
 * @see GetEventsForModeratorQueryHandler
 */
readonly class GetEventsForModeratorQuery
{
    /**
     * Create a new query instance.
     *
     * @param EventFilter $filter The filter instance containing status, organization, and search criteria.
     * @param Trooper $moderator The trooper whose permissions determine event visibility.
     * @param int $page_size Number of results per page (default: 25).
     */
    public function __construct(
        public readonly EventFilter $filter,
        public readonly Trooper $moderator,
        public readonly int $page_size = 25,
    ) {
    }
}