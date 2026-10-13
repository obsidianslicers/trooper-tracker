<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Messages\Troopers\Commands\Permissions\SendPermissionRequestReviewedNotifications;
use App\Models\PermissionRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Queues the requester and trooper notifications once a permission request is reviewed.
 */
class SendPermissionRequestReviewedNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly PermissionRequest $permission_request) {}

    public function handle(): void
    {
        SendPermissionRequestReviewedNotifications::call(
            permission_request: $this->permission_request
        );
    }
}
