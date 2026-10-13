<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Permissions;

use App\Enums\PermissionRequestStatus;
use App\Models\PermissionRequest;
use App\Notifications\Admin\PermissionRequestReviewedNotification;
use App\Notifications\Troopers\TrooperPermissionsChangedNotification;
use Hyperdrive\Message;

/**
 * Tells the requester the outcome of their permission request and, when it was
 * approved, tells the trooper their permissions changed.
 *
 * @method static void call(PermissionRequest $permission_request)
 */
final class SendPermissionRequestReviewedNotifications extends Message
{
    public function __construct(private readonly PermissionRequest $permission_request) {}

    public function handle(): void
    {
        $requester = $this->permission_request->requester;

        // An administrator reviewing their own request already knows the outcome.
        if ($requester !== null && $requester->id !== $this->permission_request->reviewer_trooper_id)
        {
            $requester->notify(new PermissionRequestReviewedNotification($this->permission_request));
        }

        if ($this->permission_request->status === PermissionRequestStatus::APPROVED)
        {
            $this->permission_request->trooper->notify(
                new TrooperPermissionsChangedNotification($this->permission_request)
            );
        }
    }
}
