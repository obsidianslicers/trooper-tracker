<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Defines the membership status of a trooper within an organization.
 */
enum MembershipRole: string
{
    use HasEnumHelpers;

    /**
     * An regular member.
     */
    case MEMBER = 'member';

    /**
     * A non-costumed handler.
     */
    case HANDLER = 'handler';

    /**
     * A member with moderation privileges.
     */
    case MODERATOR = 'moderator';

    /**
     * An regular member.
     */
    case ADMINISTRATOR = 'administrator';
}