<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Models\EventShift;
use App\Models\Trooper;

/**
 * Query to retrieve events for display based on filter criteria.
 *
 * Returns all events that match the given filter criteria, including
 * associated organizations, shifts, and other relevant details.
 *
 * @see GetEventsForDisplayQueryHandler
 */
readonly class GetEventShiftDisplayQuery
{
    /**
     * Create a new query instance.
     *
     * @param EventShift $event_shift The event shift to retrieve for display
     */
    public function __construct(
        public readonly EventShift $event_shift,
        public readonly Trooper $trooper,
    ) {
    }
}