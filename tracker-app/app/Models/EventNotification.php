<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base\EventNotification as BaseEventNotification;

/**
 * Represents a notification record for a trooper about an event.
 *
 * This model tracks which troopers have been notified about which events,
 * including when the notification was processed and sent. Used by the event
 * notification system to manage instant and daily digest notifications.
 */
class EventNotification extends BaseEventNotification
{
    use HasFactory;


}
