<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Enums\MembershipRole;

/**
 * Query to retrieve all troopers with administrator role.
 *
 * Returns all active troopers who have the ADMINISTRATOR membership_role
 * on any organization assignment. Used for sending system notifications
 * and administrative functions.
 *
 * @see GetTroopersByRoleQueryHandler
 */
readonly class GetTroopersByRoleQuery
{
    /**
     * Create a new query instance.
     *
     * No parameters required - returns all administrators.
     */
    public function __construct(public readonly MembershipRole $membership_role) {}
}
