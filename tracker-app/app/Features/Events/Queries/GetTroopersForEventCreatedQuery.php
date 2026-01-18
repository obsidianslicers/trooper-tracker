<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Models\Event;

/**
 * Query to retrieve troopers eligible for event creation notifications.
 *
 * Returns all active troopers who have opted in to receive notifications
 * for the organization hosting the event. Filters by:
 * - Active membership status
 * - Notification frequency not set to NEVER
 * - can_notify flag enabled for the event's organization
 *
 * @see GetTroopersForEventCreatedQueryHandler
 */
readonly class GetTroopersForEventCreatedQuery
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