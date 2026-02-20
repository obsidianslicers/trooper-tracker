<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

/**
 * Query to retrieve active events that need to be closed.
 *
 * Returns all active events whose end date has passed,
 * making them eligible for closure.
 *
 * @see GetEventsToCloseQueryHandler
 */
readonly class GetEventsToCloseQuery
{
    /**
     * Create a new query instance.
     *
     * No parameters required - returns all events that need closing.
     */
    public function __construct() {}
}
