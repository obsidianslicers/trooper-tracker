<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Permissions;

use App\Enums\MembershipRole;
use App\Enums\PermissionRequestStatus;
use App\Jobs\SendPermissionRequestNotificationsJob;
use App\Messages\Troopers\Queries\Permissions\GetModerationChanges;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use App\Plugins\PluginPermissionRequests;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Records a moderator's request to change a trooper's permissions and
 * notifies the administrators who can approve it.
 *
 * @method static PermissionRequest call(Trooper $trooper, MembershipRole|null $membership_role = null, array $moderated_organization_ids = [], string|null $note = null, array $extensions = [])
 */
final class RequestTrooperPermissions extends Message
{
    /**
     * @param  Actor&Trooper  $actor  The moderator submitting the request
     * @param  array<int, int|string>  $moderated_organization_ids
     * @param  array<string, array<string, mixed>>  $extensions  Validated inputs, keyed by extension key
     */
    public function __construct(
        private readonly Actor $actor,
        private readonly Trooper $trooper,
        private readonly ?MembershipRole $membership_role = null,
        private readonly array $moderated_organization_ids = [],
        private readonly ?string $note = null,
        private readonly array $extensions = [],
    ) {}

    public function handle(): PermissionRequest
    {
        $moderation = $this->moderationChanges();

        $permission_request = new PermissionRequest;

        $permission_request->trooper_id = $this->trooper->id;
        $permission_request->requester_trooper_id = $this->actor->id;
        $permission_request->membership_role = $this->membership_role;
        $permission_request->moderated_organization_ids_to_add = $moderation['add'];
        $permission_request->moderated_organization_ids_to_remove = $moderation['remove'];
        $permission_request->note = $this->note;
        $permission_request->status = PermissionRequestStatus::PENDING;

        $permission_request->save();

        foreach (app(PluginPermissionRequests::class)->all() as $key => $extension)
        {
            $extension->store($permission_request, $this->extensions[$key] ?? []);
        }

        SendPermissionRequestNotificationsJob::dispatch($permission_request);

        return $permission_request;
    }

    /**
     * @return array{add:array<int,int>,remove:array<int,int>}
     */
    private function moderationChanges(): array
    {
        if ($this->membership_role !== MembershipRole::MODERATOR)
        {
            return ['add' => [], 'remove' => []];
        }

        return GetModerationChanges::call(
            requester: $this->actor,
            trooper: $this->trooper,
            organization_ids: $this->moderated_organization_ids,
        );
    }
}
