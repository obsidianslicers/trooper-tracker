<?php

declare(strict_types=1);

namespace App\Notifications\Admin;

use App\Channels\FcmChannel;
use App\Mail\Admin\Troopers\TrooperRequestSubmitted;
use App\Models\TrooperRequest;
use App\Models\Trooper;
use Illuminate\Notifications\Notification;

class TrooperRequestSubmittedNotification extends Notification
{
    public function __construct(private readonly TrooperRequest $trooper_request)
    {
    }

    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('trooper_requests', 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification('trooper_requests', 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid()
            && $notifiable->wantsNotification('trooper_requests', 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(Trooper $notifiable): TrooperRequestSubmitted
    {
        return (new TrooperRequestSubmitted($this->trooper_request))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $trooper = $this->trooper_request->trooper;
        $org = $this->trooper_request->organization;

        return [
            'title' => 'Club Join Request',
            'body' => "{$trooper->display_name} has requested to join {$org->name}.",
            'url' => '/admin/troopers/approvals',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
