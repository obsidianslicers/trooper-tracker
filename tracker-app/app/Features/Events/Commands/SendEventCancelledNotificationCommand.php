<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\Event;
use App\Models\Trooper;

readonly class SendEventCancelledNotificationCommand
{
    /**
     * Create event notifications and send instant emails to eligible troopers.
     *
     * Iterates through the provided trooper collection and creates EventNotification
     * records for each trooper with a valid email address. Troopers with instant
     * notification preferences receive immediate emails, while others have their
     * notifications queued for daily digest processing.
     *
     * @param Event $event The event to create notifications for.
     * @param Trooper $trooper The Trooper model to notify.
     * @return void
     */
    public function __construct(
        public readonly Event $event,
        public readonly Trooper $trooper,
    ) {
    }
}
