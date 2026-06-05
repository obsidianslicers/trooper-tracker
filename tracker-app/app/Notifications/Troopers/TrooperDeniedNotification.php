<?php

declare(strict_types=1);

namespace App\Notifications\Troopers;

use App\Channels\FcmChannel;
use App\Mail\Admin\Troopers\TrooperDenied;
use App\Models\Trooper;
use Illuminate\Notifications\Notification;

/**
 * Notifies a trooper their tracker registration was denied.
 */
class TrooperDeniedNotification extends Notification
{
    public function __construct(private readonly ?string $denial_reason = null) {}

    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('trooper_denied', 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification('trooper_denied', 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid()
            && $notifiable->wantsNotification('trooper_denied', 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(Trooper $notifiable): TrooperDenied
    {
        return (new TrooperDenied($notifiable, $this->denial_reason))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $body = 'Your registration was not approved.';

        if ($this->denial_reason)
        {
            $body .= " Reason: {$this->denial_reason}";
        }

        return [
            'title' => 'Registration Not Approved',
            'body' => $body,
            'url' => '/account',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
