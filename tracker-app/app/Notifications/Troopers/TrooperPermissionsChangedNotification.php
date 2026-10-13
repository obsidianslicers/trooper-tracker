<?php

declare(strict_types=1);

namespace App\Notifications\Troopers;

use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Mail\Admin\Troopers\TrooperPermissionsChanged;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use App\Notifications\BaseNotification;

/**
 * Notifies a trooper that an approved permission request changed their role or access.
 */
class TrooperPermissionsChangedNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category =
        'permissions_changed';

    public function __construct(private readonly PermissionRequest $permission_request) {}

    public function toMail(Trooper $notifiable): TrooperPermissionsChanged
    {
        return (new TrooperPermissionsChanged($this->permission_request))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'Permissions Changed',
            'body' => 'An administrator has updated your permissions.',
            'url' => '/account',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
