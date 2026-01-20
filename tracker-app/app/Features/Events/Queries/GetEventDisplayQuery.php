<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Models\Event;
use App\Models\Trooper;

/**
 * Query to retrieve events for display based on filter criteria.
 *
 * Returns all events that match the given filter criteria, including
 * associated organizations, shifts, and other relevant details.
 *
 * @see GetEventsForDisplayQueryHandler
 */
readonly class GetEventDisplayQuery
{
    /**
     * Create a new query instance.
     *
     * @param Event $event The event to retrieve for display
     */
    public function __construct(
        public readonly Event $event,
        public readonly Trooper $trooper,
    ) {
    }
}