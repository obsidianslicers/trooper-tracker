<?php

declare(strict_types=1);

namespace App\Notifications\Troopers;

use App\Channels\FcmChannel;
use App\Mail\Admin\Troopers\VisitorAccessExpired;
use App\Models\Trooper;
use Illuminate\Notifications\Notification;

class VisitorAccessExpiredNotification extends Notification
{
    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('visitor_access_expired', 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification('visitor_access_expired', 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid()
            && $notifiable->wantsNotification('visitor_access_expired', 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(Trooper $notifiable): VisitorAccessExpired
    {
        return (new VisitorAccessExpired($notifiable))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'Visitor Access Expired',
            'body'  => 'Your 6-month visitor access has expired. Log in to request renewal.',
            'url'   => '/account/visitor-renew',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
