<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Models\Event;

/**
 * Query to retrieve event shifts with troopers for admin display.
 *
 * Retrieves all shifts for an event with enriched trooper and costume
 * information for administrative viewing.
 *
 * @see GetTroopersForEventAdminQueryHandler
 */
readonly class GetTroopersForEventAdminQuery
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