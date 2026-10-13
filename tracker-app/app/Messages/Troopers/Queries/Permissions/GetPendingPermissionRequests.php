<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Permissions;

use App\Models\PermissionRequest;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Lists every permission request waiting on an administrator, oldest first.
 *
 * @method static Collection<int, PermissionRequest> call()
 */
final class GetPendingPermissionRequests extends Message
{
    public function __construct() {}

    /**
     * @return Collection<int, PermissionRequest>
     */
    public function handle(): Collection
    {
        return PermissionRequest::with(['trooper', 'requester', 'reviewer'])
            ->pending()
            ->orderBy(PermissionRequest::CREATED_AT)
            ->get();
    }
}
