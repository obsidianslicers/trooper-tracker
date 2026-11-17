<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Mail\Events\TentativeStatusReminderMail;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Notifications\BaseNotification;

class TentativeStatusReminderNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'tentative_reminder';

    public function __construct(private readonly EventTrooper $event_trooper) {}

    public function toMail(Trooper $notifiable): TentativeStatusReminderMail
    {
        return (new TentativeStatusReminderMail($this->event_trooper))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $event = $this->event_trooper->event_shift->event;
        $days_until = (int) now()->diffInDays($event->event_start, false);

        return [
            'title' => __('notifications.Update Your Tentative Status: :name', ['name' => $event->name]),
            'body' => trans_choice(
                'notifications.You have :count day to confirm or cancel your tentative status.|You have :count days to confirm or cancel your tentative status.',
                $days_until
            ),
            'url' => '/events/details/'.$event->id,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
