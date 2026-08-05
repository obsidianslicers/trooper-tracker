<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Mail\Events\CancelledEventNotification;
use App\Models\Event;
use App\Models\Trooper;
use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Notifications\BaseNotification;

class EventCancelledNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'event_cancelled';

    public function __construct(private readonly Event $event)
    {
    }

    public function toMail(Trooper $notifiable): CancelledEventNotification
    {
        return (new CancelledEventNotification($this->event))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'Event Cancelled: ' . $this->event->name,
            'body' => 'This event has been cancelled.',
            'url' => '/events/cancelled',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
