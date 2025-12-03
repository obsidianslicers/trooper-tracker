<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Defines the types of notifications that can be displayed to a user.
 *
 * This is typically used for flash messages or alerts to convey information
 * with a certain level of importance or context (e.g., success, error).
 */
enum NoticeType: string
{
    use HasEnumHelpers;

    /**
     * For informational messages.
     */
    case INFO = 'info';
    /**
     * For success messages (e.g., after a form is submitted correctly).
     */
    case SUCCESS = 'success';
    /**
     * For warnings that require user attention.
     */
    case WARNING = 'warning';
    /**
     * For critical errors or failure messages.
     */
    case DANGER = 'danger';

    /**
     * Return the description for a single enum case.
     */
    public function description(): string
    {
        return match ($this)
        {
            self::INFO => 'NOW HEAR THIS!',
            self::SUCCESS => 'MISSION ACCOMPLISHED!',
            self::WARNING => 'ATTENTION TROOPERS!',
            self::DANGER => 'BATTLE STATIONS!',
        };
    }


    /**
     * Summary of toDescriptions
     * @return array{danger: string, info: string, success: string, warning: string}
     */
    public static function toDescriptions(): array
    {
        return [
            'info' => self::INFO->description(),
            'success' => self::SUCCESS->description(),
            'warning' => self::WARNING->description(),
            'danger' => self::DANGER->description(),
        ];
    }
}