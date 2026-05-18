<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Defines the status of a trooper's request to join a club organization.
 */
enum JoinRequestStatus: string
{
    use HasEnumHelpers;

    /**
     * Request is awaiting moderator review.
     */
    case PENDING = 'pending';

    /**
     * Request was approved; trooper added to the organization.
     */
    case APPROVED = 'approved';

    /**
     * Request was denied by a moderator.
     */
    case DENIED = 'denied';
}
