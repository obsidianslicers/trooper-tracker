<?php

declare(strict_types=1);

namespace App\Notifications\Troopers;

use App\Channels\FcmChannel;
use App\Mail\Admin\Troopers\DirectlyAddedToClub;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Notifications\Notification;

/**
 * Notifies a trooper they have been directly added to a club by a moderator.
 */
class DirectlyAddedToClubNotification extends Notification
{
    public function __construct(private readonly Organization $organization) {}

    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('directly_added_to_club', 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification('directly_added_to_club', 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid()
            && $notifiable->wantsNotification('directly_added_to_club', 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(Trooper $notifiable): DirectlyAddedToClub
    {
        return (new DirectlyAddedToClub($notifiable, $this->organization))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'Added to Club',
            'body'  => "You have been added to {$this->organization->name}.",
            'url'   => '/account/club-memberships',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
