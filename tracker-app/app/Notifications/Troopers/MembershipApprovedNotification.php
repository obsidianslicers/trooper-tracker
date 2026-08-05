<?php

declare(strict_types=1);

namespace App\Notifications\Troopers;

use App\Mail\Admin\Troopers\TrooperApproved;
use App\Models\Trooper;
use App\Notifications\BaseNotification;
use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;

class MembershipApprovedNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'membership_approved';

    public function toMail(Trooper $notifiable): TrooperApproved
    {
        return (new TrooperApproved($notifiable))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'Membership Approved',
            'body' => 'Welcome, Trooper!',
            'url' => '/account/profile',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
