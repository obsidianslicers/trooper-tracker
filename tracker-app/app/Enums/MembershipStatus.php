<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Defines the membership status of a trooper within an organization.
 */
enum MembershipStatus: string
{
    use HasEnumHelpers;

    /**
     * Not a member of the organization.
     */
    case NONE = 'none';

    /**
     * Membership application is pending approval.
     */
    case PENDING = 'pending';

    /**
     * Denied member of the organization.
     */
    case DENIED = 'denied';

    /**
     * Active member of the organization.
     */
    case ACTIVE = 'active';

    /**
     * Inactive member of the organization.
     */
    case INACTIVE = 'inactive';

    /**
     * Invalid member of the organization.
     */
    case INVALID = 'invalid';

    /**
     * A member on reserve status.
     */
    case RESERVE = 'reserve';

    /**
     * A retired member.
     */
    case RETIRED = 'retired';

    /**
     * Account created in error — merged into another account;
     *  excluded from all normal views and cannot log in.
     */
    case MERGED = 'merged';

    /**
     * Member has passed away — service record shows a memorial tribute; cannot log in.
     */
    case DEPARTED = 'departed';

    /**
     * Whether a trooper with this account status may sign in and use the tracker.
     */
    public function canLogIn(): bool
    {
        return $this === self::ACTIVE || $this === self::RESERVE;
    }

    /**
     * Whether only an administrator may set or clear this status, because it locks the trooper
     * out and has its own administrator tool (void, R.I.P. or merge).
     */
    public function isAdminOnly(): bool
    {
        return in_array($this, [self::INVALID, self::DEPARTED, self::MERGED], true);
    }

    /**
     * The statuses a trooper may pick from when changing a trooper's status, keeping the
     * subject's current status so the form can still show it.
     *
     * @return array<string, string>
     */
    public static function choicesFor(bool $is_administrator, ?self $current = null): array
    {
        return array_filter(
            self::toArray(),
            fn (string $value) => $is_administrator
                || !self::from($value)->isAdminOnly()
                || $value === $current?->value,
            ARRAY_FILTER_USE_KEY,
        );
    }
}
