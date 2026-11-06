<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Models\Event;
use App\Models\Trooper;
use App\Notifications\Events\EventCancelledNotification;
use Hyperdrive\Message;

/**
 * Command to send event cancellation notification to a trooper.
 *
 * Triggers an email notification to a trooper who had signed up for an event
 * that has been cancelled. Unlike regular event notifications, cancellation
 * notifications are sent regardless of the trooper's notification preferences.
 *
 * @method static void call(Event $event, Trooper $trooper)
 */
final class SendEventCancelledNotification extends Message
{
    /**
     * Create a new command instance.
     *
     * @param  Event  $event  The cancelled event.
     * @param  Trooper  $trooper  The trooper who signed up for the event.
     */
    public function __construct(
        private readonly Event $event,
        private readonly Trooper $trooper,
    ) {}

    public function handle(): void
    {
        $this->trooper->notify(new EventCancelledNotification($this->event));
    }
}
