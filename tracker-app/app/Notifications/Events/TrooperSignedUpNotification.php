<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Channels\FcmChannel;
use App\Mail\Events\TrooperSignUp;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Notifications\Notification;

class TrooperSignedUpNotification extends Notification
{
    public function __construct(private readonly EventTrooper $event_trooper) {}

    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('trooper_signed_up', 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification('trooper_signed_up', 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid()
            && $notifiable->wantsNotification('trooper_signed_up', 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(Trooper $notifiable): TrooperSignUp
    {
        return (new TrooperSignUp($this->event_trooper))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $event = $this->event_trooper->event_shift->event;

        return [
            'title' => 'Event Sign-Up Confirmed',
            'body'  => $event->name,
            'url'   => '/events/details/'.$event->id,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
