<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Defines notification categories for trooper preferences.
 */
enum NotificationPreferences: string
{
    use HasEnumHelpers;

    /**
     * Sent when a new event is created.
     */
    case EVENT_CREATED = 'event_created';

    /**
     * Sent when an event is cancelled.
     */
    case EVENT_CANCELLED = 'event_cancelled';

    /**
     * Sent when a trooper signs up for an event.
     */
    case TROOPER_SIGNED_UP = 'trooper_signed_up';

    /**
     * Sent when an event shift is completed.
     */
    case EVENT_SHIFT_COMPLETED = 'event_shift_completed';
}
