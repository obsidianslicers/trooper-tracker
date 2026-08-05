<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Mail\Events\TrooperSignUp;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Notifications\BaseNotification;

class TrooperSignedUpNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'trooper_signed_up';

    public function __construct(private readonly EventTrooper $event_trooper) {}

    public function toMail(Trooper $notifiable): TrooperSignUp
    {
        return (new TrooperSignUp($this->event_trooper))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $event = $this->event_trooper->event_shift->event;

        return [
            'title' => 'Event Sign-Up Confirmed',
            'body' => $event->name,
            'url' => '/events/details/'.$event->id,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
