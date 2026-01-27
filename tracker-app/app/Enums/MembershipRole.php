<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Defines the membership role of a trooper within the organization.
 *
 * Membership roles control access levels and privileges within the Troop Tracker system.
 */
enum MembershipRole: string
{
    use HasEnumHelpers;

    /**
     * A regular member with standard access.
     */
    case MEMBER = 'member';

    /**
     * A non-costumed handler who assists at events.
     */
    case HANDLER = 'handler';

    /**
     * A member with moderation privileges to manage events and members.
     */
    case MODERATOR = 'moderator';

    /**
     * An administrator with full system access and control.
     */
    case ADMINISTRATOR = 'administrator';
}