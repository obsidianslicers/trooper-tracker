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
     * Sent when troopers are merged.
     */
    case TROOPERS_MERGED = 'troopers_merged';

    /**
     * Sent to command staff when a trooper flags a forum post for their attention.
     */
    case FORUM_POST_COMMAND_STAFF = 'forum_post_command_staff';

    /**
     * Sent when a trooper earns a milestone achievement (troop counts, donation milestones, etc.).
     */
    case TROOPER_MILESTONES = 'trooper_milestones';

    /**
     * Sent to administrators when a moderator requests permission changes for a trooper.
     */
    case PERMISSION_REQUESTS = 'permission_requests';

    /**
     * Sent to the moderator who requested permission changes once an administrator reviews it.
     */
    case PERMISSION_REQUEST_REVIEWED = 'permission_request_reviewed';

    /**
     * Categories that are only ever sent to administrators, never to moderators.
     *
     * @return array<int, self>
     */
    public static function administratorOnly(): array
    {
        return [self::TROOPERS_MERGED, self::PERMISSION_REQUESTS];
    }
}
