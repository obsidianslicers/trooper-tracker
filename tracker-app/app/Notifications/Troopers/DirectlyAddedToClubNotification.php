<?php

declare(strict_types=1);

namespace App\Notifications\Troopers;

use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Mail\Admin\Troopers\DirectlyAddedToClub;
use App\Models\Organization;
use App\Models\Trooper;
use App\Notifications\BaseNotification;

/**
 * Notifies a trooper they have been directly added to a club by a moderator.
 */
class DirectlyAddedToClubNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'directly_added_to_club';

    public function __construct(private readonly Organization $organization) {}

    public function toMail(Trooper $notifiable): DirectlyAddedToClub
    {
        return (new DirectlyAddedToClub($notifiable, $this->organization))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'Added to Club',
            'body' => "You have been added to {$this->organization->name}.",
            'url' => '/account/club-memberships',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
