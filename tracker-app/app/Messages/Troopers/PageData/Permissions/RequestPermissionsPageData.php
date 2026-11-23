<?php

declare(strict_types=1);

namespace App\Messages\Troopers\PageData\Permissions;

use App\Messages\Troopers\Queries\Permissions\GetRequestableOrganizations;
use App\Messages\Troopers\Queries\Permissions\GetRequestedPermissionRequests;
use App\Messages\Troopers\Resources\PermissionRequestsCollection;
use App\Messages\Troopers\Resources\RequestableOrganizationResource;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Data for the page where moderators request permission changes for a trooper.
 *
 * @method static array<string, mixed> call()
 */
final class RequestPermissionsPageData extends Message
{
    /**
     * @param  Actor&Trooper  $actor  The moderator or administrator viewing the page
     */
    public function __construct(private readonly Actor $actor) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return [
            'organizations' => RequestableOrganizationResource::collection(
                GetRequestableOrganizations::call(requester: $this->actor)->values()
            ),
            'permission_requests' => new PermissionRequestsCollection(
                GetRequestedPermissionRequests::call(requester: $this->actor)
            ),
        ];
    }
}
