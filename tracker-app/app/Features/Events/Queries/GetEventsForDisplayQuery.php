<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

/**
 * Query to retrieve all events for list display.
 *
 * Returns all events with associated organizations and shifts,
 * ordered appropriately for list/calendar views.
 *
 * @see GetEventsForDisplayQueryHandler
 */
readonly class GetEventsForDisplayQuery
{
    /**
     * Create a new query instance.
     *
     * No parameters required - returns all events.
     */
    public function __construct() {}
}
