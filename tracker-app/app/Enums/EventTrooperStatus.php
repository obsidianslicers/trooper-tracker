<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Defines the possible statuses for a trooper's attendance at an event.
 */
enum EventTrooperStatus: string
{
    use HasEnumHelpers;

    /**
     * No status set.
     */
    case NONE = 'none';
    /**
     * Trooper is confirmed to be going.
     */
    case GOING = 'going';
    /**
     * Trooper is on standby for the event.
     */
    case STAND_BY = 'standby';
    /**
     * Trooper is tentatively planning to attend.
     */
    case TENTATIVE = 'tentative';
    /**
     * Trooper has attended the event.
     */
    case ATTENDED = 'attended';
    /**
     * Trooper has canceled their attendance.
     */
    case CANCELLED = 'cancelled';
    /**
     * Trooper's attendance is pending approval.
     */
    case PENDING = 'pending';
    /**
     * Trooper was not selected for a limited event.
     */
    case NOT_PICKED = 'notpicked';
    /**
     * Trooper was confirmed but did not show up.
     */
    case NO_SHOW = 'noshow';

    public static function toSignUpArray(): array
    {
        return [
            self::GOING->value => to_title(self::GOING->name),
            self::STAND_BY->value => to_title(self::STAND_BY->name),
            self::TENTATIVE->value => to_title(self::TENTATIVE->name),
            self::CANCELLED->value => to_title(self::CANCELLED->name),
        ];
    }
}
