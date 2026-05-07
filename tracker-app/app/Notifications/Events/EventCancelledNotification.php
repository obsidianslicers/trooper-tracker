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
        $channels = ['database'];

        if ($notifiable->push_notifications_enabled)
        {
            $channels[] = FcmChannel::class;
        }

        // Cancellations always send email regardless of notification_frequency
        if ($notifiable->emailAppearsValid())
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
