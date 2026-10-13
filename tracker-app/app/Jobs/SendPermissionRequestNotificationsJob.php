<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Messages\Troopers\Commands\Permissions\SendPermissionRequestNotifications;
use App\Models\PermissionRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Queues the administrator notifications for a new permission request.
 */
class SendPermissionRequestNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly PermissionRequest $permission_request) {}

    public function handle(): void
    {
        SendPermissionRequestNotifications::call(permission_request: $this->permission_request);
    }
}
