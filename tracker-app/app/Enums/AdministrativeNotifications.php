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
     * Sent when a trooper earns a milestone achievement (troop counts, donation milestones, etc.).
     */
    case TROOPER_MILESTONES = 'trooper_milestones';

    /**
     * Sent when a trooper adds a costume or club that needs officer approval.
     */
    case COSTUME_REQUESTS = 'costume_requests';

    /**
     * Sent to administrators when a moderator requests permission changes for a trooper.
     */
    case PERMISSION_REQUESTS = 'permission_requests';

    /**
     * Sent to administrators when troop verifications have waited more than 30 days.
     */
    case STALE_VERIFICATIONS = 'stale_verifications';

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
        return [self::TROOPERS_MERGED, self::PERMISSION_REQUESTS, self::STALE_VERIFICATIONS];
    }
}
