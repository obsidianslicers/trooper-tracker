<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Permissions;

use App\Enums\MembershipRole;
use App\Enums\PermissionRequestStatus;
use App\Jobs\SendPermissionRequestNotificationsJob;
use App\Messages\Troopers\Queries\Permissions\GetRequestableForumGroups;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Records a moderator's request to change a trooper's permissions and
 * notifies the administrators who can approve it.
 *
 * @method static PermissionRequest call(Trooper $trooper, MembershipRole|null $membership_role = null, array $moderated_organization_ids = [], array $forum_group_ids_to_add = [], array $forum_group_ids_to_remove = [], string|null $note = null)
 */
final class RequestTrooperPermissions extends Message
{
    /**
     * @param  Actor&Trooper  $actor  The moderator submitting the request
     * @param  array<int, int|string>  $moderated_organization_ids
     * @param  array<int, int|string>  $forum_group_ids_to_add
     * @param  array<int, int|string>  $forum_group_ids_to_remove
     */
    public function __construct(
        private readonly Actor $actor,
        private readonly Trooper $trooper,
        private readonly ?MembershipRole $membership_role = null,
        private readonly array $moderated_organization_ids = [],
        private readonly array $forum_group_ids_to_add = [],
        private readonly array $forum_group_ids_to_remove = [],
        private readonly ?string $note = null,
    ) {}

    public function handle(): PermissionRequest
    {
        $forum_groups = $this->hasForumGroups() ? GetRequestableForumGroups::call() : collect();

        $permission_request = new PermissionRequest;

        $permission_request->trooper_id = $this->trooper->id;
        $permission_request->requester_trooper_id = $this->actor->id;
        $permission_request->membership_role = $this->membership_role;
        $permission_request->moderated_organization_ids = $this->organizationIds();
        $permission_request->forum_groups_to_add = $this->pickGroups(
            $forum_groups,
            $this->forum_group_ids_to_add
        );
        $permission_request->forum_groups_to_remove = $this->pickGroups(
            $forum_groups,
            $this->forum_group_ids_to_remove
        );
        $permission_request->note = $this->note;
        $permission_request->status = PermissionRequestStatus::PENDING;

        $permission_request->save();

        SendPermissionRequestNotificationsJob::dispatch($permission_request);

        return $permission_request;
    }

    private function hasForumGroups(): bool
    {
        return $this->forum_group_ids_to_add !== [] || $this->forum_group_ids_to_remove !== [];
    }

    /**
     * @return array<int, int>
     */
    private function organizationIds(): array
    {
        if ($this->membership_role !== MembershipRole::MODERATOR)
        {
            return [];
        }

        return collect($this->moderated_organization_ids)
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Snapshot the group titles so the request still reads correctly if the
     * forum renames or deletes a group later.
     *
     * @param  Collection<int, array{id:int,title:string}>  $forum_groups
     * @param  array<int, int|string>  $group_ids
     * @return array<int, array{id:int,title:string}>
     */
    private function pickGroups(Collection $forum_groups, array $group_ids): array
    {
        $ids = collect($group_ids)->map(fn (int|string $id): int => (int) $id);

        return $forum_groups
            ->filter(fn (array $group): bool => $ids->contains($group['id']))
            ->values()
            ->all();
    }
}
