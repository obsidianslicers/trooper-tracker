<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

/**
 * Query to retrieve active event shifts that need to be reminded.
 *
 * Returns all active event shifts whose start time is approaching,
 * making them eligible for reminder notifications.
 *
 * @see GetEventShiftsToRemindQueryHandler
 */
readonly class GetEventShiftsToRemindQuery
{
    /**
     * Create a new query instance.
     *
     * No parameters required - returns all event shifts that need reminding.
     */
    public function __construct() {}
}
