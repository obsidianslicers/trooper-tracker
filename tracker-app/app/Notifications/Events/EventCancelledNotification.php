<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Channels\FcmChannel;
use App\Mail\Events\CancelledEventNotification;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Notifications\Notification;

class EventCancelledNotification extends Notification
{
    public function __construct(private readonly Event $event) {}

    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('event_cancelled', 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification('event_cancelled', 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid()
            && $notifiable->wantsNotification('event_cancelled', 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(Trooper $notifiable): CancelledEventNotification
    {
        return (new CancelledEventNotification($this->event))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'Event Cancelled: '.$this->event->name,
            'body'  => 'This event has been cancelled.',
            'url'   => '/events/cancelled',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
