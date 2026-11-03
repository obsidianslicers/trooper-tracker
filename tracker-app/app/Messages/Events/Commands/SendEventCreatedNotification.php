<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Models\Event;
use App\Models\Trooper;
use App\Notifications\Events\EventCreatedNotification;
use Hyperdrive\Message;

/**
 * Command to create event notification for a trooper when a new event is published.
 *
 * Creates an EventNotification record and optionally sends an instant email
 * based on the trooper's notification frequency preference (INSTANT vs DAILY).
 *
 * @method static void call(Event $event, Trooper $trooper)
 */
final class SendEventCreatedNotification extends Message
{
    /**
     * Create a new command instance.
     *
     * @param  Event  $event  The event to create notification for.
     * @param  Trooper  $trooper  The trooper to notify.
     */
    public function __construct(
        private readonly Event $event,
        private readonly Trooper $trooper,
    ) {}

    public function handle(): void
    {
        $this->trooper->notify(new EventCreatedNotification($this->event));
    }
}
