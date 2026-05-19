<?php

declare(strict_types=1);

namespace App\Notifications\Troopers;

use App\Channels\FcmChannel;
use App\Mail\Admin\Troopers\JoinRequestApproved;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Notifications\Notification;

/**
 * Notifies a trooper their club join request was approved.
 */
class JoinRequestApprovedNotification extends Notification
{
    public function __construct(private readonly Organization $organization) {}

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

    public function toMail(Trooper $notifiable): JoinRequestApproved
    {
        return (new JoinRequestApproved($notifiable, $this->organization))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'Join Request Approved',
            'body'  => "You've been added to {$this->organization->name}!",
            'url'   => '/account/club-memberships',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
