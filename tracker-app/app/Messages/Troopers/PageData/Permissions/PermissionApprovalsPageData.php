<?php

declare(strict_types=1);

namespace App\Messages\Troopers\PageData\Permissions;

use App\Messages\Troopers\Queries\Permissions\GetPendingPermissionRequests;
use App\Messages\Troopers\Resources\PermissionRequestsCollection;
use Hyperdrive\Message;

/**
 * Data for the administrator page that approves or denies permission requests.
 *
 * @method static array<string, mixed> call()
 */
final class PermissionApprovalsPageData extends Message
{
    public function __construct() {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return [
            'permission_requests' => new PermissionRequestsCollection(GetPendingPermissionRequests::call()),
        ];
    }
}
