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
     * Trooper signed up but hasn't yet decided on a costume (picked a real
     * costume or explicitly chosen to attend without one). Not counted as
     * GOING until that decision is made.
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
    /**
     * Trooper was unable to attend.
     */
    case UNABLE_TO_ATTEND = 'unabletoattend';

    /**
     * Get an array of available sign-up statuses.
     *
     * Returns the statuses that a trooper can select when signing up for an event.
     * If tentative sign-ups are allowed, includes the TENTATIVE option.
     *
     * @param  bool  $tentative_signups_allowed  Whether tentative status is available
     * @return array<string, string> Array with status values as keys and formatted names as values
     */
    public static function toSignUpArray(bool $tentative_signups_allowed, bool $is_limited = false): array
    {
        $options = [
            self::GOING->value => to_title(self::GOING->name),
            self::CANCELLED->value => to_title(self::CANCELLED->name),
        ];

        if ($tentative_signups_allowed && !$is_limited)
        {
            $options[self::TENTATIVE->value] = to_title(self::TENTATIVE->name);
        }

        return $options;
    }

    /**
     * Get the Font Awesome icon class for this status.
     *
     * @return string The Font Awesome icon class (e.g., 'fa-circle-play')
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
            self::UNABLE_TO_ATTEND => 'fa-circle-xmark',
        };
    }

    /**
     * Get the Bootstrap text color class for this status.
     *
     * @return string The Bootstrap color class (e.g., 'text-success', 'text-danger')
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
            self::UNABLE_TO_ATTEND => 'text-danger',
        };
    }

    /**
     * Get a ready-to-use HTML icon tag with appropriate color styling.
     *
     * Combines the Font Awesome icon class and Bootstrap color class into
     * a formatted <i> tag that can be inserted directly into templates.
     *
     * @return string HTML <i> tag with icon and color classes
     */
    public function iconTag(): string
    {
        return sprintf('<i class="fa fa-fw %s %s ms-2"></i>', $this->icon(), $this->color());
    }

    /**
     * Determine if the trooper intends to go based on their status.
     *
     * A trooper is considered to intend to go if their status is either GOING or TENTATIVE,
     * as both indicate some level of commitment to attending the event.
     *
     * @return bool True if the status indicates intent to go, false otherwise
     */
    public function intendsToGo(): bool
    {
        return in_array($this, self::intentToGoArray());
    }

    /**
     * Get an array of statuses that indicate the trooper intends to go.
     *
     * This includes both GOING and TENTATIVE statuses, as both represent
     * some level of intent to attend the event.
     *
     * @return array<EventTrooperStatus> Array of statuses indicating intent to go
     */
    public static function intentToGoArray(): array
    {
        return [self::GOING, self::TENTATIVE];
    }
}
