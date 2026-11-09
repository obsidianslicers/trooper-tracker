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
     * A visitor with temporary, time-limited access to the top-level organization.
     */
    case VISITOR = 'visitor';

    /**
     * A member who creates events for organizations they belong to and manages the
     * events they created. Not command staff: no other admin access, notifications
     * or forum permissions.
     */
    case EVENT_COORDINATOR = 'event_coordinator';

    /**
     * A member with moderation privileges to manage events and members.
     */
    case MODERATOR = 'moderator';

    /**
     * An administrator without the developer tools (Plugins, System Check, Merge).
     */
    case STAFF_OFFICER = 'staff_officer';

    /**
     * The System Owner: full access, including the developer tools. Stored as "administrator".
     */
    case SYSTEM_OWNER = 'administrator';

    public function toAccountType(): string
    {
        return match ($this)
        {
            MembershipRole::VISITOR, MembershipRole::HANDLER => $this->value,
            default => 'member',
        };
    }

    /**
     * Whether the role carries administrator access (System Owner or Staff Officer).
     */
    public function hasAdministratorAccess(): bool
    {
        return $this === self::SYSTEM_OWNER || $this === self::STAFF_OFFICER;
    }

    /**
     * Whether the role may use the developer tools (Plugins, System Check, Merge).
     */
    public function hasSystemOwner(): bool
    {
        return $this === self::SYSTEM_OWNER;
    }

    public function isHandler(): bool
    {
        return $this === self::HANDLER;
    }

    public function isVisitor(): bool
    {
        return $this === self::VISITOR;
    }
}
