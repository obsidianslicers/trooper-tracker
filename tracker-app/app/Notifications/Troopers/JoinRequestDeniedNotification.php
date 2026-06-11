<?php

declare(strict_types=1);

namespace App\Notifications\Troopers;

use App\Channels\FcmChannel;
use App\Mail\Admin\Troopers\JoinRequestDenied;
use App\Models\TrooperRequest;
use App\Models\Trooper;
use Illuminate\Notifications\Notification;

/**
 * Notifies a trooper their club join request was denied.
 */
class JoinRequestDeniedNotification extends Notification
{
    public function __construct(private readonly TrooperRequest $trooper_request)
    {
    }

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

        if ($notifiable->emailAppearsValid()
            && $notifiable->wantsNotification('join_request_denied', 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(Trooper $notifiable): JoinRequestDenied
    {
        return (new JoinRequestDenied(
            $notifiable,
            $this->trooper_request->organization,
            $this->trooper_request->denial_reason,
        ))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $body = "Your request to join {$this->trooper_request->organization->name} was not approved.";

        if ($this->trooper_request->denial_reason)
        {
            $body .= " Reason: {$this->trooper_request->denial_reason}";
        }

        return [
            'title' => 'Join Request Not Approved',
            'body' => $body,
            'url' => '/account/club-memberships',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
