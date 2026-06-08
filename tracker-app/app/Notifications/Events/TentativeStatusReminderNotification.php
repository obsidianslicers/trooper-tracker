<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Channels\FcmChannel;
use App\Mail\Events\TentativeStatusReminderMail;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Notifications\Notification;

class TentativeStatusReminderNotification extends Notification
{
    public function __construct(private readonly EventTrooper $event_trooper) {}

    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('tentative_reminder', 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification('tentative_reminder', 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid()
            && $notifiable->wantsNotification('tentative_reminder', 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
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
            'title' => 'Update Your Tentative Status: '.$event->name,
            'body'  => "You have {$days_until} day(s) to confirm or cancel your tentative status.",
            'url'   => route('events.display', $event),
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
