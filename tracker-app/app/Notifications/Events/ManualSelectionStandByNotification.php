<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Channels\FcmChannel;
use App\Mail\Events\TrooperManualSelectionStandBy;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Notifications\Notification;

class ManualSelectionStandByNotification extends Notification
{
    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly Trooper $changed_by,
    ) {}

    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('manual_selection_stand_by', 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification('manual_selection_stand_by', 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid()
            && $notifiable->wantsNotification('manual_selection_stand_by', 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(Trooper $notifiable): TrooperManualSelectionStandBy
    {
        return (new TrooperManualSelectionStandBy($this->event_trooper, $this->changed_by))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $event = $this->event_trooper->event_shift->event;

        return [
            'title' => 'Moved to Stand By',
            'body'  => $event->name,
            'url'   => '/events/details/'.$event->id,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
