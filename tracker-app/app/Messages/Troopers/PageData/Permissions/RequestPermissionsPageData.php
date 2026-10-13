<?php

declare(strict_types=1);

namespace App\Messages\Troopers\PageData\Permissions;

use App\Facades\TroopTrackerFacade;
use App\Messages\Troopers\Queries\Permissions\GetRequestableForumGroups;
use App\Messages\Troopers\Queries\Permissions\GetRequestableOrganizations;
use App\Messages\Troopers\Queries\Permissions\GetRequestedPermissionRequests;
use App\Messages\Troopers\Resources\PermissionRequestsCollection;
use App\Models\Organization;
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
        $is_xenforo_configured = TroopTrackerFacade::isXenforoIntegrationConfigured();

        return [
            'organizations' => $this->getOrganizations(),
            'is_xenforo_configured' => $is_xenforo_configured,
            'forum_groups' => $is_xenforo_configured
                ? GetRequestableForumGroups::call()->all()
                : [],
            'permission_requests' => new PermissionRequestsCollection(
                GetRequestedPermissionRequests::call(requester: $this->actor)
            ),
        ];
    }

    /**
     * @return array<int, array{id:int,name:string,parent_name:string|null,node_path:string,depth:int}>
     */
    private function getOrganizations(): array
    {
        return GetRequestableOrganizations::call(requester: $this->actor)
            ->map(fn (Organization $organization): array => [
                'id' => $organization->id,
                'name' => $organization->name,
                'parent_name' => $organization->parent?->name,
                'node_path' => (string) $organization->node_path,
                'depth' => (int) $organization->depth,
            ])
            ->values()
            ->all();
    }
}
