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
}
