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
        $request = $this->permission_request;
        $requester = $request->requester;

        // An administrator reviewing their own request already knows the outcome.
        if ($requester !== null && $requester->id !== $request->reviewer_trooper_id)
        {
            $requester->notify(new PermissionRequestReviewedNotification($request));
        }

        if ($request->status === PermissionRequestStatus::APPROVED)
        {
            $request->trooper->notify(new TrooperPermissionsChangedNotification($request));
        }
    }
}
