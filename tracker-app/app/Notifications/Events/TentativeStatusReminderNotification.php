<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Mail\Events\TentativeStatusReminderMail;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Notifications\BaseNotification;

class TentativeStatusReminderNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'tentative_reminder';

    public function __construct(private readonly EventTrooper $event_trooper)
    {
    }

    public function toMail(Trooper $notifiable): TentativeStatusReminderMail
    {
        return (new TentativeStatusReminderMail($this->event_trooper))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $event = $this->event_trooper->event_shift->event;
        $days_until = (int) now()->diffInDays($event->event_start, false);

        return [
            'title' => 'Update Your Tentative Status: ' . $event->name,
            'body' => "You have {$days_until} day(s) to confirm or cancel your tentative status.",
            'url' => '/events/details/' . $event->id,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
