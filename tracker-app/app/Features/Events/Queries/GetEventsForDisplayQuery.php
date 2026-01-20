<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

/**
 * Query to retrieve events for display based on filter criteria.
 *
 * Returns all events that match the given filter criteria, including
 * associated organizations, shifts, and other relevant details.
 *
 * @see GetEventsForDisplayQueryHandler
 */
readonly class GetEventsForDisplayQuery
{
    /**
     * Create a new query instance.
     *
     */
    public function __construct()
    {
    }
}