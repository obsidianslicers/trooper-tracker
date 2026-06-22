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
    case TROOPER_REQUESTS = 'trooper_requests';

    /**
     * Sent when a new trooper registration is submitted.
     */
    case TROOPER_REGISTRATIONS = 'trooper_registrations';

    /**
     * Sent to command staff when a trooper flags a forum post for their attention.
     */
    case FORUM_POST_COMMAND_STAFF = 'forum_post_command_staff';

    /**
     * Sent when a trooper earns a milestone achievement (troop counts, donation milestones, etc.).
     */
    case TROOPER_MILESTONES = 'trooper_milestones';
}
