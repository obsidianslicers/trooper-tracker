<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Messages\Troopers\Queries\GetAdministrators;
use App\Models\PermissionRequest;
use App\Notifications\Admin\PermissionRequestSubmittedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Notifies every administrator that a moderator submitted a permission request.
 *
 * Only administrators can approve permission requests, so moderators are not notified.
 */
class SendPermissionRequestNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly PermissionRequest $permission_request) {}

    public function handle(): void
    {
        $admins = GetAdministrators::call();

        foreach ($admins as $admin)
        {
            $admin->notify(new PermissionRequestSubmittedNotification($this->permission_request));
        }
    }
}
