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
     * Get the Star Wars-themed description for this notice type.
     *
     * @return string Themed message based on notice type
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
     * Get an array of all notice type descriptions.
     *
     * @return array{danger: string, info: string, success: string, warning: string} Associative array of type => description
     */
    public static function toDescriptions(): array
    {
        return [
            'info' => 'NOW HEAR THIS!',
            'success' => 'MISSION ACCOMPLISHED!',
            'warning' => 'ATTENTION TROOPERS!',
            'danger' => 'BATTLE STATIONS!'
        ];
    }

    /**
     * Get an array of notice type options with emoji icons.
     *
     * @return array{danger: string, info: string, success: string, warning: string} Associative array of type => emoji + description
     */
    public static function toOptions(): array
    {
        return [
            'info' => 'ℹ️ NOW HEAR THIS!',
            'success' => '✔️ MISSION ACCOMPLISHED!',
            'warning' => '⚠️ ATTENTION TROOPERS!',
            'danger' => '❌ BATTLE STATIONS!',
        ];
    }
}