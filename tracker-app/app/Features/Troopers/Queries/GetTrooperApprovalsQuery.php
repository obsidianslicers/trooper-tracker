<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Models\Trooper;

/**
 * Query to retrieve pending trooper registrations for approval.
 *
 * Returns troopers with PENDING membership_status who are awaiting approval.
 * If the requesting trooper is a moderator (not administrator), results are
 * filtered to only troopers pending for organizations they moderate.
 *
 * @see GetTrooperApprovalsQueryHandler
 */
readonly class GetTrooperApprovalsQuery
{
    /**
     * Create a new query instance.
     *
     * @param Trooper $moderator The trooper acting as moderator/administrator
     */
    public function __construct(public readonly Trooper $moderator)
    {
    }
}