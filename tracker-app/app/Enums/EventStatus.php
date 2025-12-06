<?php

declare(strict_types=1);

namespace App\Enums;

/** 
 * Defines the possible statuses for an event.
 */
enum EventStatus: string
{
    use HasEnumHelpers;

    /**
     * The event is in draft mode 
     */
    case DRAFT = 'draft';
    /**
     * The event is open for sign-ups and is upcoming.
     */
    case OPEN = 'open';
    /**
     * The event has concluded.
     */
    case CLOSED = 'closed';
    /**
     * The event has been canceled.
     */
    case CANCELED = 'canceled';
    /**
     * The event has been locked.
     */
    case LOCKED = 'locked';
}