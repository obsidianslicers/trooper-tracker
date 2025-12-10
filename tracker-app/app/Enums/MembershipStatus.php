<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Defines the membership status of a trooper within an organization.
 */
enum MembershipStatus: string
{
    use HasEnumHelpers;

    /**
     * Not a member of the organization.
     */
    case NONE = 'none';

    /**
     * Membership application is pending approval.
     */
    case PENDING = 'pending';

    /**
     * Denied member of the organization.
     */
    case DENIED = 'denied';

    /**
     * Active member of the organization.
     */
    case ACTIVE = 'active';

    /**
     * A member on reserve status.
     */
    case RESERVE = 'reserve';

    /**
     * A retired member.
     */
    case RETIRED = 'retired';

    /**
     * Not a member of the organization.
     */
    case NOT_FOUND = 'notfound';
}