<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Channels\FcmChannel;
use App\Mail\Events\CancelledEventNotification;
use App\Mail\Events\EventShiftComplete;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Notifications\Notification;

class EventShiftCompletedNotification extends Notification
{
    public function __construct(private readonly EventTrooper $event_trooper)
    {
    }

    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('event_shift_completed', 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification('event_shift_completed', 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid()
            && $notifiable->wantsNotification('event_shift_completed', 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(Trooper $notifiable): EventShiftComplete
    {

        return (new EventShiftComplete($this->event_trooper))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $event_id = $this->event_trooper->event_shift->event->id;
        $shift_id = $this->event_trooper->event_shift->id;
        $name = $this->event_trooper->event_shift->event->name;
        $time_display = $this->event_trooper->event_shift->time_display;

        return [
            'title' => "Event Shift Completed for {$name}",
            'body' => "This event shift {$time_display} has completed.",
            'url' => "/events/details/{$event_id}#shift-{$shift_id}",
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
