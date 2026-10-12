<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Permissions;

use App\Enums\PermissionRequestStatus;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use RuntimeException;

/**
 * Denies a permission request without changing the trooper.
 *
 * @method static void call(PermissionRequest $permission_request, string|null $denial_reason = null)
 */
final class DenyTrooperPermissions extends Message
{
    /**
     * @param  Actor&Trooper  $actor  The administrator denying the request
     */
    public function __construct(
        private readonly Actor $actor,
        private readonly PermissionRequest $permission_request,
        private readonly ?string $denial_reason = null,
    ) {}

    public function handle(): void
    {
        if (!$this->permission_request->isPending())
        {
            throw new RuntimeException('Permission request is not pending.');
        }

        $this->permission_request->status = PermissionRequestStatus::DENIED;
        $this->permission_request->denial_reason = $this->denial_reason;
        $this->permission_request->reviewer_trooper_id = $this->actor->id;
        $this->permission_request->reviewed_at = now();
        $this->permission_request->save();
    }
}
