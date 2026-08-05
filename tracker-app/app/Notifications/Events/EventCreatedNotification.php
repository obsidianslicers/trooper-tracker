<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Channels\DailyDigestChannel;
use App\Channels\FcmChannel;
use App\Enums\AdministrativeNotifications;
use App\Enums\NotificationFrequency;
use App\Enums\TrooperNotifications;
use App\Mail\Events\InstantEventNotification;
use App\Models\Event;
use App\Models\EventNotification;
use App\Models\Trooper;
use App\Notifications\BaseNotification;

class EventCreatedNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'event_created';

    public function __construct(private readonly Event $event) {}

    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification($this->notification_category, 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification($this->notification_category, 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->notification_frequency === NotificationFrequency::INSTANT
            && $notifiable->emailAppearsValid()
            && $notifiable->wantsNotification($this->notification_category, 'mail'))
        {
            $channels[] = 'mail';
        }
        elseif ($notifiable->notification_frequency === NotificationFrequency::DAILY
            && $notifiable->wantsNotification($this->notification_category, 'mail'))
        {
            $channels[] = DailyDigestChannel::class;
        }

        return $channels;
    }

    public function toMail(Trooper $notifiable): InstantEventNotification
    {
        $notification = EventNotification::firstOrCreate(
            [
                EventNotification::EVENT_ID => $this->event->id,
                EventNotification::TROOPER_ID => $notifiable->id,
            ],
            [EventNotification::PROCESSED_AT => now()],
        );

        $notification->processed_at = now();
        $notification->save();

        return (new InstantEventNotification($notification))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'New Event: '.$this->event->name,
            'body' => $this->event->venue ?? 'See Troop Tracker for details',
            'url' => '/events/details/'.$this->event->id,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    public function toDailyDigest(Trooper $notifiable): array
    {
        return ['event_id' => $this->event->id];
    }
}
