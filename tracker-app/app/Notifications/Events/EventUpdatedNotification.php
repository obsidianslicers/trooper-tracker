<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Channels\FcmChannel;
use App\Mail\Events\EventUpdatedMail;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Notifications\Notification;

class EventUpdatedNotification extends Notification
{
    public function __construct(
        private readonly Event $event,
        private readonly array $changed_fields,
    ) {}

    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('event_updated', 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification('event_updated', 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid()
            && $notifiable->wantsNotification('event_updated', 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(Trooper $notifiable): EventUpdatedMail
    {
        return (new EventUpdatedMail($this->event, $this->changed_fields))
            ->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'Event Updated: '.$this->event->name,
            'body'  => 'Details for this event have changed. Please review.',
            'url'   => '/events/details/'.$this->event->id,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
