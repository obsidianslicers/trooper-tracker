<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Channels\FcmChannel;
use App\Mail\Events\TrooperNextInLine;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Notifications\Notification;

class TrooperPromotedToGoingNotification extends Notification
{
    public function __construct(private readonly EventTrooper $event_trooper) {}

    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('trooper_promoted_to_going', 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification('trooper_promoted_to_going', 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid()
            && $notifiable->wantsNotification('trooper_promoted_to_going', 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(Trooper $notifiable): TrooperNextInLine
    {
        return (new TrooperNextInLine($this->event_trooper))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $event = $this->event_trooper->event_shift->event;

        return [
            'title' => "You're Now Going!",
            'body' => $event->name,
            'url' => '/events/details/'.$event->id,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
