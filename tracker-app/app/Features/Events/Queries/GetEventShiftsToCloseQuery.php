<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

/**
 * Query to retrieve active event shifts that need to be closed.
 *
 * Returns all active event shifts whose end time has passed,
 * making them eligible for closure.
 *
 * @see GetEventShiftsToCloseQueryHandler
 */
readonly class GetEventShiftsToCloseQuery
{
    /**
     * Create a new query instance.
     *
     * No parameters required - returns all event shifts that need closing.
     */
    public function __construct() {}
}
