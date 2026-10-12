<?php

declare(strict_types=1);

namespace App\Notifications\Admin;

use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Mail\Admin\Troopers\PermissionRequestSubmitted;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use App\Notifications\BaseNotification;

class PermissionRequestSubmittedNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category =
        AdministrativeNotifications::PERMISSION_REQUESTS;

    public function __construct(private readonly PermissionRequest $permission_request) {}

    public function toMail(Trooper $notifiable): PermissionRequestSubmitted
    {
        return (new PermissionRequestSubmitted($this->permission_request))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $requester = $this->permission_request->requester?->display_name ?? 'A moderator';
        $trooper = $this->permission_request->trooper->display_name;

        return [
            'title' => 'Permission Request',
            'body' => "{$requester} has requested permission changes for {$trooper}.",
            'url' => '/admin/troopers/permissions/approvals',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
