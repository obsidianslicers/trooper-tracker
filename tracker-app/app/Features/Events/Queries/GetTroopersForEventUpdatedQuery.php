<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Models\Event;

/**
 * Query to retrieve troopers eligible for event update notifications.
 *
 * Returns troopers watching the event plus troopers on the event's roster
 * whose sign-up status indicates intent to attend.
 *
 * @see GetTroopersForEventUpdatedQueryHandler
 */
readonly class GetTroopersForEventUpdatedQuery
{
    /**
     * Create a new query instance.
     *
     * @param  Event  $event  The event for which to retrieve troopers
     */
    public function __construct(public readonly Event $event) {}
}
