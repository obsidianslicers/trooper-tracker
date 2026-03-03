<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Models\EventShare;

/**
 * Query to retrieve a shared event roster for external viewing.
 *
 * Returns the specified event share with eagerly loaded relationships including
 * the associated event, trooper who shared it, and roster details for display
 * to external recipients without authentication.
 *
 * @see GetSharedEventRosterQueryHandler
 */
readonly class GetSharedEventRosterQuery
{
    /**
     * Create a new query instance.
     *
     * @param  EventShare  $event_share  The event share to retrieve for display
     */
    public function __construct(
        public readonly EventShare $event_share,
    ) {}
}
