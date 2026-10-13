<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Defines the review status of a moderator's permission request for a trooper.
 */
enum PermissionRequestStatus: string
{
    use HasEnumHelpers;

    /**
     * The permission request is waiting on an administrator.
     */
    case PENDING = 'pending';

    /**
     * The permission request was approved and applied.
     */
    case APPROVED = 'approved';

    /**
     * The permission request was denied.
     */
    case DENIED = 'denied';
}
