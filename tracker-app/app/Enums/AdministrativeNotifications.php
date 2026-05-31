<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Defines notification categories for administrative notifications.
 */
enum AdministrativeNotifications: string
{
    use HasEnumHelpers;

    /**
     * Sent when there are new club join requests.
     */
    case JOIN_REQUESTS = 'join_requests';

    /**
     * Sent when a new trooper registration is submitted.
     */
    case TROOPER_REGISTRATIONS = 'trooper_registrations';
}
