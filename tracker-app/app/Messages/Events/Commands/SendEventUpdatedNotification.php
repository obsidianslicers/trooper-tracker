<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Models\Event;
use App\Models\Trooper;
use App\Notifications\Events\EventUpdatedNotification;
use Hyperdrive\Message;

/**
 * Command to send an event-updated notification to a trooper.
 *
 * @method static void call(Event $event, Trooper $trooper, array $changed_fields)
 */
final class SendEventUpdatedNotification extends Message
{
    /**
     * Create a new command instance.
     *
     * @param  Event  $event  The event that was updated.
     * @param  Trooper  $trooper  The trooper to notify.
     * @param  array<int, string>  $changed_fields  The event attributes that changed.
     */
    public function __construct(
        private readonly Event $event,
        private readonly Trooper $trooper,
        private readonly array $changed_fields,
    ) {}

    public function handle(): void
    {
        $this->trooper->notify(new EventUpdatedNotification($this->event, $this->changed_fields));
    }
}
