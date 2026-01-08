<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Defines the frequency at which notifications can be sent.
 */
enum NotificationFrequency: string
{
    use HasEnumHelpers;

    /**
     * The notification is not sent.
     */
    case NEVER = 'never';

    /**
     * The notification is given out instantly.
     */
    case INSTANT = 'instant';

    /**
     * The notification is given out on a daily basis.
     */
    case DAILY = 'daily';
}