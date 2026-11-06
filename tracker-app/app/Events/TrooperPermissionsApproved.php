<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\PermissionRequest;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Command staff approved a request to change a trooper's role or moderated organizations.
 */
final class TrooperPermissionsApproved implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly PermissionRequest $permission_request) {}
}
