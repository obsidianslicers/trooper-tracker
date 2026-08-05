<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Mail\Events\EventUpdatedMail;
use App\Models\Event;
use App\Models\Trooper;
use App\Notifications\BaseNotification;

class EventUpdatedNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'event_updated';

    public function __construct(
        private readonly Event $event,
        private readonly array $changed_fields,
    ) {}

    public function toMail(Trooper $notifiable): EventUpdatedMail
    {
        return (new EventUpdatedMail($this->event, $this->changed_fields))
            ->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'Event Updated: '.$this->event->name,
            'body' => 'Details for this event have changed. Please review.',
            'url' => '/events/details/'.$this->event->id,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
