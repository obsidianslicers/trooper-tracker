<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Channels\FcmChannel;
use App\Mail\Events\TrooperManualSelectionApproved;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Notifications\Notification;

class ManualSelectionApprovedNotification extends Notification
{
    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly Trooper $approved_by,
    ) {}

    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('manual_selection_approved', 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification('manual_selection_approved', 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid()
            && $notifiable->wantsNotification('manual_selection_approved', 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(Trooper $notifiable): TrooperManualSelectionApproved
    {
        return (new TrooperManualSelectionApproved($this->event_trooper, $this->approved_by))->to($notifiable->email);
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
