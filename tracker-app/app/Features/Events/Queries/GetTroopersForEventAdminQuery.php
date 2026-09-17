<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Models\Event;

/**
 * Query to retrieve event shifts with troopers for admin display and roster editing.
 *
 * Retrieves all shifts for an event with enriched trooper and costume
 * information for administrative viewing, plus the editable costume/org
 * option lists used by the roster edit form.
 *
 * @see GetTroopersForEventAdminQueryHandler
 */
readonly class GetTroopersForEventAdminQuery
{
    /**
     * Create a new query instance.
     *
     * @param  Event  $event  The event for which to retrieve troopers
     * @param  array<int, int>|null  $allowed_org_ids  Moderator's scoped org ids, or null for no restriction (admin)
     */
    public function __construct(
        public readonly Event $event,
        public readonly ?array $allowed_org_ids = null,
    ) {}
}
