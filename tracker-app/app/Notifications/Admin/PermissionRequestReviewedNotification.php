<?php

declare(strict_types=1);

namespace App\Notifications\Admin;

use App\Enums\AdministrativeNotifications;
use App\Enums\PermissionRequestStatus;
use App\Enums\TrooperNotifications;
use App\Mail\Admin\Troopers\PermissionRequestReviewed;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use App\Notifications\BaseNotification;

/**
 * Notifies the moderator who submitted a permission request that it was approved or denied.
 */
class PermissionRequestReviewedNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category =
        AdministrativeNotifications::PERMISSION_REQUEST_REVIEWED;

    public function __construct(private readonly PermissionRequest $permission_request) {}

    public function toMail(Trooper $notifiable): PermissionRequestReviewed
    {
        return (new PermissionRequestReviewed($this->permission_request))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $trooper = $this->permission_request->trooper->display_name;
        $decision = $this->permission_request->status === PermissionRequestStatus::APPROVED
            ? 'approved'
            : 'denied';

        return [
            'title' => 'Permission Request '.ucfirst($decision),
            'body' => "Your permission request for {$trooper} was {$decision}.",
            'url' => '/admin/troopers/permissions',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
