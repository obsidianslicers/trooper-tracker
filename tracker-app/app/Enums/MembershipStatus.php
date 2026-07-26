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
     * Inactive member of the organization.
     */
    case INACTIVE = 'inactive';

    /**
     * Invalid member of the organization.
     */
    case INVALID = 'invalid';

    /**
     * A member on reserve status.
     */
    case RESERVE = 'reserve';

    /**
     * A retired member.
     */
    case RETIRED = 'retired';

    /**
     * Account created in error — merged into another account;
     *  excluded from all normal views and cannot log in.
     */
    case MERGED = 'merged';

    /**
     * Member has passed away — service record shows a memorial tribute; cannot log in.
     */
    case DEPARTED = 'departed';
}
