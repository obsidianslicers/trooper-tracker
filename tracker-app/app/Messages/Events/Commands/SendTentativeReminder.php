<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Models\EventTrooper;
use App\Notifications\Events\TentativeStatusReminderNotification;
use Hyperdrive\Message;

/**
 * @method static void call(EventTrooper $event_trooper)
 */
final class SendTentativeReminder extends Message
{
    public function __construct(private readonly EventTrooper $event_trooper) {}

    public function handle(): void
    {
        $this->event_trooper->trooper->notify(
            new TentativeStatusReminderNotification($this->event_trooper)
        );
    }
}
