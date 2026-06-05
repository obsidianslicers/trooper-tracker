<?php

declare(strict_types=1);

namespace App\Notifications\Admin;

use App\Channels\FcmChannel;
use App\Mail\Admin\Troopers\TrooperAwaitingApproval;
use App\Models\Trooper;
use Illuminate\Notifications\Notification;

class TrooperRegisteredNotification extends Notification
{
    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('trooper_registrations', 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification('trooper_registrations', 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid()
            && $notifiable->wantsNotification('trooper_registrations', 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(Trooper $notifiable): TrooperAwaitingApproval
    {
        return (new TrooperAwaitingApproval)->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'New Trooper Registration',
            'body' => 'A new trooper is awaiting approval.',
            'url' => '/admin/troopers/approvals',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
