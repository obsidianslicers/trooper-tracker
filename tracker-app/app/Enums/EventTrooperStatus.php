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

    public static function toSignUpArray(bool $tentative_signups_allowed): array
    {
        $options = [
            self::GOING->value => to_title(self::GOING->name),
            self::STAND_BY->value => to_title(self::STAND_BY->name),
            self::CANCELLED->value => to_title(self::CANCELLED->name),
        ];

        if ($tentative_signups_allowed)
        {
            $options[self::TENTATIVE->value] = to_title(self::TENTATIVE->name);
        }

        return $options;
    }

    /**
     * Return the Font Awesome icon class for this status.
     */
    public function icon(): string
    {
        return match ($this)
        {
            self::NONE => 'fa-circle-question',
            self::GOING => 'fa-circle-play',
            self::STAND_BY => 'fa-circle-pause',
            self::TENTATIVE => 'fa-circle-dot',
            self::ATTENDED => 'fa-user-check',
            self::CANCELLED => 'fa-times-circle',
            self::PENDING => 'fa-hourglass-half',
            self::NOT_PICKED => 'fa-ban',
            self::NO_SHOW => 'fa-user-slash',
        };
    }

    /**
     * Return the Font Awesome icon class for this status.
     */
    public function color(): string
    {
        return match ($this)
        {
            self::NONE => 'text-muted',
            self::GOING => 'text-success',
            self::STAND_BY => 'text-warning',
            self::TENTATIVE => 'text-warning',
            self::ATTENDED => 'text-success',
            self::CANCELLED => 'text-danger',
            self::PENDING => 'text-info',
            self::NOT_PICKED => 'text-secondary',
            self::NO_SHOW => 'text-muted',
        };
    }

    /**
     * Optionally return a ready-to-use <i> tag.
     */
    public function iconTag(): string
    {
        return sprintf('<i class="fa fa-fw %s %s ms-2"></i>', $this->icon(), $this->color());
    }
}
