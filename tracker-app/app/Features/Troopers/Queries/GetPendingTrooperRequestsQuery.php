<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Models\Trooper;

/**
 * Query to retrieve pending club join requests visible to a moderator.
 *
 * Results are scoped to organizations within the moderator's tree.
 * Administrators see all pending requests.
 *
 * @see GetPendingTrooperRequestsQueryHandler
 */
readonly class GetPendingTrooperRequestsQuery
{
    /**
     * @param  Trooper  $moderator  The moderator or administrator viewing the queue
     */
    public function __construct(public readonly Trooper $moderator) {}
}
