<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

/**
 * Query to retrieve troopers who need daily notification digests.
 *
 * Returns active troopers who have opted for daily notification
 * frequency and have unprocessed event notifications pending.
 *
 * @see GetTroopersForDailyEventNotificationsQueryHandler
 */
readonly class GetTroopersForDailyEventNotificationsQuery
{
    /**
     * Create a new query instance.
     *
     * No parameters required - returns all troopers needing daily notifications.
     */
    public function __construct()
    {
    }
}
