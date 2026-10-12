<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Permissions;

use App\Messages\Troopers\Queries\GetAdministrators;
use App\Models\PermissionRequest;
use App\Notifications\Admin\PermissionRequestSubmittedNotification;
use Hyperdrive\Message;

/**
 * Notifies every administrator that a permission request is waiting for review.
 *
 * Only administrators can approve permission requests, so moderators are not notified.
 *
 * @method static void call(PermissionRequest $permission_request)
 */
final class SendPermissionRequestNotifications extends Message
{
    public function __construct(private readonly PermissionRequest $permission_request) {}

    public function handle(): void
    {
        foreach (GetAdministrators::call() as $admin)
        {
            $admin->notify(new PermissionRequestSubmittedNotification($this->permission_request));
        }
    }
}
