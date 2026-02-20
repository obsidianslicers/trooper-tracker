<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Models\EventShift;
use App\Models\Trooper;

/**
 * Query to retrieve a single event shift for display with all related data.
 *
 * Returns the specified event shift with eagerly loaded relationships including
 * the parent event, trooper sign-ups, costumes, and other relevant details.
 *
 * @see GetEventShiftDisplayQueryHandler
 */
readonly class GetEventShiftDisplayQuery
{
    /**
     * Create a new query instance.
     *
     * @param  EventShift  $event_shift  The event shift to retrieve for display
     * @param  Trooper  $trooper  The trooper viewing the event shift
     */
    public function __construct(
        public readonly EventShift $event_shift,
        public readonly Trooper $trooper,
    ) {}
}
