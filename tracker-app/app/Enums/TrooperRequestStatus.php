<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Defines the trooper request status of a trooper within an organization.
 */
enum TrooperRequestStatus: string
{
    use HasEnumHelpers;

    /**
     * The trooper request is pending approval.
     */
    case PENDING = 'pending';

    /**
     * The trooper request has been approved.
     */
    case APPROVED = 'approved';

    /**
     * The trooper request has been denied.
     */
    case DENIED = 'denied';
}
