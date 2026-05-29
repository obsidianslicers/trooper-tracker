<?php

declare(strict_types=1);

namespace App\Notifications\Troopers;

use App\Channels\FcmChannel;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Notifications\Notification;

/**
 * Notifies a trooper their club join request was denied.
 * No email is sent on denial, matching the existing trooper denial pattern.
 */
class JoinRequestDeniedNotification extends Notification
{
    public function __construct(private readonly Organization $organization) {}

    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('join_request_denied', 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification('join_request_denied', 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'Join Request Denied',
            'body'  => "Your request to join {$this->organization->name} was not approved.",
            'url'   => '/account/club-memberships',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
