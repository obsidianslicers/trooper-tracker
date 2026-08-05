<?php

declare(strict_types=1);

namespace App\Notifications\Troopers;

use App\Mail\Admin\Troopers\VisitorAccessExpired;
use App\Models\Trooper;
use App\Notifications\BaseNotification;
use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;

class VisitorAccessExpiredNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'visitor_access_expired';

    public function toMail(Trooper $notifiable): VisitorAccessExpired
    {
        return (new VisitorAccessExpired($notifiable))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'Visitor Access Expired',
            'body' => 'Your 6-month visitor access has expired. Log in to request renewal.',
            'url' => '/account/visitor-renew',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
