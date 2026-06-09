<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Defines the possible statuses for a guest's attendance at an event.
 */
enum EventGuestStatus: string
{
    use HasEnumHelpers;

    /**
     * Guest is on stand-by and requires approval.
     */
    case STAND_BY = 'standby';

    /**
     * Guest is confirmed to be going.
     */
    case GOING = 'going';
    /**
     * Guest is tentatively planning to attend.
     */
    case TENTATIVE = 'tentative';
    /**
     * Guest has canceled their attendance.
     */
    case CANCELLED = 'cancelled';

    /**
     * Get an array of statuses available for guest self-selection.
     *
     * Excludes STAND_BY, which is set only by moderators via the approval flow.
     *
     * @return array<string, string> Array with status values as keys and formatted names as values
     */
    public static function toSelectArray(): array
    {
        return [
            self::GOING->value => to_title(self::GOING->name),
            self::TENTATIVE->value => to_title(self::TENTATIVE->name),
            self::CANCELLED->value => to_title(self::CANCELLED->name),
        ];
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
            self::STAND_BY => 'fa-circle-pause',
            self::GOING => 'fa-circle-play',
            self::TENTATIVE => 'fa-circle-dot',
            self::CANCELLED => 'fa-times-circle',
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
            self::STAND_BY => 'text-warning',
            self::GOING => 'text-success',
            self::TENTATIVE => 'text-warning',
            self::CANCELLED => 'text-danger',
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
     * Determine if the guest intends to go based on their status.
     *
     * A guest is considered to intend to go if their status is either GOING or TENTATIVE,
     * as both indicate some level of commitment to attending the event.
     *
     * @return bool True if the status indicates intent to go, false otherwise
     */
    public function intendsToGo(): bool
    {
        return in_array($this, self::intentToGoArray());
    }

    /**
     * Get an array of statuses that indicate the guest intends to go.
     *
     * This includes both GOING and TENTATIVE statuses, as both represent
     * some level of intent to attend the event.
     *
     * @return array<EventGuestStatus> Array of statuses indicating intent to go
     */
    public static function intentToGoArray(): array
    {
        return [self::GOING, self::TENTATIVE];
    }
}
