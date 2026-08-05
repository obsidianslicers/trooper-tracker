<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Mail\Events\TrooperNextInLine;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Notifications\BaseNotification;

class TrooperPromotedToGoingNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'trooper_promoted_to_going';

    public function __construct(private readonly EventTrooper $event_trooper)
    {
    }

    public function toMail(Trooper $notifiable): TrooperNextInLine
    {
        return (new TrooperNextInLine($this->event_trooper))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $event = $this->event_trooper->event_shift->event;

        return [
            'title' => "You're Now Going!",
            'body' => $event->name,
            'url' => '/events/details/' . $event->id,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
