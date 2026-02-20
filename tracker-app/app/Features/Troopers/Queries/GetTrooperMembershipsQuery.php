<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Models\Trooper;

/**
 * Query to retrieve membership assignments for a trooper.
 *
 * Returns the organizational hierarchy with each organization marked
 * to indicate whether the trooper is a member of that organization
 * (is_member flag on TrooperAssignment).
 *
 * @see GetTrooperMembershipsQueryHandler
 */
readonly class GetTrooperMembershipsQuery
{
    /**
     * Create a new query instance.
     *
     * @param  Trooper  $trooper  The trooper whose memberships to retrieve
     */
    public function __construct(public readonly Trooper $trooper) {}
}
