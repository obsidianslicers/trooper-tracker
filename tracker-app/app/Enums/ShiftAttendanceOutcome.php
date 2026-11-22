<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What happened when a trooper answered "did you make it?" for a shift (ConfirmShiftAttendance).
 */
enum ShiftAttendanceOutcome: string
{
    /**
     * The answer is on the record.
     */
    case RECORDED = 'recorded';

    /**
     * A moderator confirms the troop before it counts: the trooper's troops need verification,
     * or this one was already sent for it.
     */
    case AWAITING_VERIFICATION = 'awaiting_verification';

    /**
     * The costume can credit more than one club, so the trooper picks which before it's recorded.
     */
    case CHOOSE_CLUB = 'choose_club';

    /**
     * The event is past its grace period; only a moderator can change the record now.
     */
    case CLOSED = 'closed';

    public const VERIFICATION_MESSAGE = 'Thanks, trooper! A moderator will confirm this troop '
        .'before it shows on your service record.';

    public const CLOSED_MESSAGE = 'Status updates for this event are no longer permitted. '
        .'Please reach out to your administrator for further help.';

    /**
     * The line to show the trooper. An empty string for CHOOSE_CLUB, which shows the club choice instead.
     */
    public function message(EventTrooperStatus $answer): string
    {
        return match ($this)
        {
            self::RECORDED => $answer === EventTrooperStatus::ATTENDED
                ? self::attendedMessages()[self::messageOfTheDay()]
                : self::absentMessages()[self::messageOfTheDay()],
            self::AWAITING_VERIFICATION => __('events.'.self::VERIFICATION_MESSAGE),
            self::CLOSED => __('events.'.self::CLOSED_MESSAGE),
            self::CHOOSE_CLUB => '',
        };
    }

    private static function messageOfTheDay(): int
    {
        return (now()->day - 1) % 12;
    }

    /**
     * @return array<int, string>
     */
    private static function attendedMessages(): array
    {
        return [
            __('events.Excellent work trooper! Imperial records now reflect your heroic presence.'),
            __('events.Your dedication has been logged in the Imperial archives.'),
            __('events.Another successful mission completed. The Empire salutes you.'),
            __('events.Your presence was felt across the sector. Well done.'),
            __('events.Your armor shines brighter in the records today.'),
            __('events.The Emperor himself acknowledges your contribution.'),
            __("events.Your actions strengthen the Empire's grip on the galaxy."),
            __('events.Your service brings stability to the Outer Rim.'),
            __('events.Your discipline is noted and appreciated.'),
            __('events.Your efforts echo through the Imperial halls.'),
            __('events.Your loyalty has been recorded with precision.'),
            __('events.Your mission performance has exceeded expectations.'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function absentMessages(): array
    {
        return [
            __('events.The dark side sensed your effort.'),
            __('events.Your signal was faint, trooper. The Empire noticed.'),
            __('events.Your armor remained cold this time. Perhaps next mission.'),
            __('events.The Emperor felt a disturbance... your absence.'),
            __('events.Your squad marched on without you.'),
            __('events.Imperial scanners reported no movement from your position.'),
            __('events.Your presence was missed on the battlefield.'),
            __('events.The mission proceeded, though your post was empty.'),
            __('events.Your absence has been logged in the archives.'),
            __('events.The Empire awaits your return.'),
            __('events.Your squadmates carried the banner without you.'),
            __('events.The mission concluded, but your armor remained untouched.'),
        ];
    }
}
