<?php

declare(strict_types=1);

namespace App\Notifications\Troopers;

use App\Channels\FcmChannel;
use App\Mail\Admin\Troopers\TrooperApproved;
use App\Models\Trooper;
use Illuminate\Notifications\Notification;

class MembershipApprovedNotification extends Notification
{
    public function via(Trooper $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->push_notifications_enabled)
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid())
        {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(Trooper $notifiable): TrooperApproved
    {
        return (new TrooperApproved($notifiable))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'Membership Approved',
            'body'  => 'Welcome, Trooper!',
            'url'   => '/account/profile',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
