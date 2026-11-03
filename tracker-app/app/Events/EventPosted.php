<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Event;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An event has been posted and troopers have been told about it.
 *
 * Fired by SendEventCreatedNotificationsJob, both when an event is created open
 * and when a draft event is opened later.
 */
final class EventPosted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Event $event) {}
}
