<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Mail\Events\BroadcastEventMessage;
use App\Models\Event;
use App\Models\Trooper;
use App\Notifications\BaseNotification;

class BroadcastEventMessageNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'event_broadcast';

    public function __construct(private readonly Event $event, private readonly string $message) {}

    public function toMail(Trooper $notifiable): BroadcastEventMessage
    {
        return (new BroadcastEventMessage($this->event, $this->message))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'Event Message: '.$this->event->name,
            'body' => $this->message,
            'url' => '/events/'.$this->event->id,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
