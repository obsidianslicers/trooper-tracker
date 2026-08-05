<?php

declare(strict_types=1);

namespace App\Notifications\Admin;

use App\Mail\Admin\Troopers\TrooperAwaitingApproval;
use App\Models\Trooper;
use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Notifications\BaseNotification;

class TrooperRegisteredNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'trooper_registrations';

    public function toMail(Trooper $notifiable): TrooperAwaitingApproval
    {
        return (new TrooperAwaitingApproval())->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'New Trooper Registration',
            'body' => 'A new trooper is awaiting approval.',
            'url' => '/admin/troopers/approvals',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
