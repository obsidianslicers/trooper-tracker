<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Models\Event;

/**
 * Query to retrieve troopers who signed up for a cancelled event.
 *
 * Returns all active troopers who have a "going" status for any shift
 * in the cancelled event. These troopers should receive cancellation
 * notifications via email.
 *
 * @see GetTroopersForEventCancelledQueryHandler
 */
readonly class GetTroopersForEventCancelledQuery
{
    /**
     * Create a new query instance.
     *
     * @param Event $event The event for which to retrieve troopers
     */
    public function __construct(public readonly Event $event)
    {
    }
}