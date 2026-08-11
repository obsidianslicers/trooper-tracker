<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationChannels: string
{
    use HasEnumHelpers;

    /**
     * Summary of MAIL
     */
    case MAIL = 'mail';

    /**
     * Summary of FMC
     */
    case FMC = 'fcm';

    /**
     * Summary of DATABASE
     */
    case DATABASE = 'database';
}