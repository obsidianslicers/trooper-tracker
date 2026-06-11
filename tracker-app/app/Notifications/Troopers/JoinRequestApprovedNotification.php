<?php

declare(strict_types=1);

namespace App\Notifications\Troopers;

use App\Channels\FcmChannel;
use App\Mail\Admin\Troopers\JoinRequestApproved;
use App\Models\TrooperRequest;
use App\Models\Trooper;
use Illuminate\Notifications\Notification;

/**
 * Notifies a trooper their club join request was approved.
 */
class JoinRequestApprovedNotification extends Notification
{
    public function __construct(private readonly TrooperRequest $trooper_request)
    {
    }

    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('join_request_approved', 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification('join_request_approved', 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid()
            && $notifiable->wantsNotification('join_request_approved', 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(Trooper $notifiable): JoinRequestApproved
    {
        return (new JoinRequestApproved($notifiable, $this->trooper_request->organization))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'Join Request Approved',
            'body' => "You've been added to {$this->trooper_request->organization->name}!",
            'url' => '/account/club-memberships',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
