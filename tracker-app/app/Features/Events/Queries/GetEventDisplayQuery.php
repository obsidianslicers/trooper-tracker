<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Models\Event;
use App\Models\Trooper;

/**
 * Query to retrieve a single event for display with all related data.
 *
 * Returns the specified event with eagerly loaded relationships including
 * organizations, shifts, trooper sign-ups, and other relevant details.
 *
 * @see GetEventDisplayQueryHandler
 */
readonly class GetEventDisplayQuery
{
    /**
     * Create a new query instance.
     *
     * @param Event $event The event to retrieve for display
     * @param Trooper $trooper The trooper viewing the event
     */
    public function __construct(
        public readonly Event $event,
        public readonly Trooper $trooper,
    ) {
    }
}