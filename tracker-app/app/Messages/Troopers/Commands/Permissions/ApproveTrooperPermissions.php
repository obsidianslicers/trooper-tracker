<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Permissions;

use App\Enums\MembershipRole;
use App\Enums\PermissionRequestStatus;
use App\Exceptions\ForumGroupUpdateException;
use App\Jobs\SendPermissionRequestReviewedNotificationsJob;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use RuntimeException;

/**
 * Approves a permission request: applies the requested role and moderated
 * organizations, then updates the trooper's XenForo user groups.
 *
 * The forum change runs last, inside the transaction, so a forum failure
 * rolls back the role change and leaves the request pending.
 *
 * @method static void call(PermissionRequest $permission_request)
 */
final class ApproveTrooperPermissions extends Message
{
    use ShouldBeTransactional;

    /**
     * @param  Actor&Trooper  $actor  The administrator approving the request
     */
    public function __construct(
        private readonly Actor $actor,
        private readonly PermissionRequest $permission_request,
    ) {}

    /**
     * @throws ForumGroupUpdateException
     */
    public function handle(): void
    {
        if (!$this->permission_request->isPending())
        {
            throw new RuntimeException('Permission request is not pending.');
        }

        $trooper = $this->permission_request->trooper;

        $this->applyMembershipRole($trooper);

        UpdateTrooperForumGroups::call(
            trooper: $trooper,
            group_ids_to_add: $this->groupIds($this->permission_request->forum_groups_to_add),
            group_ids_to_remove: $this->groupIds($this->permission_request->forum_groups_to_remove),
        );

        $this->permission_request->status = PermissionRequestStatus::APPROVED;
        $this->permission_request->reviewer_trooper_id = $this->actor->id;
        $this->permission_request->reviewed_at = now();
        $this->permission_request->save();

        // After commit so a rolled-back approval never notifies anyone.
        SendPermissionRequestReviewedNotificationsJob::dispatch($this->permission_request)
            ->afterCommit();
    }

    private function applyMembershipRole(Trooper $trooper): void
    {
        $role = $this->permission_request->membership_role;

        if ($role === null)
        {
            return;
        }

        if ($trooper->membership_role === MembershipRole::ADMINISTRATOR)
        {
            throw new RuntimeException(
                'Administrator roles cannot be changed by a permission request.'
            );
        }

        $trooper->membership_role = $role;
        $trooper->visitor_expires_at = null;
        $trooper->visitor_notified_at = null;
        $trooper->save();

        if ($role === MembershipRole::MODERATOR)
        {
            $this->assignModeratedOrganizations($trooper);
            $this->unassignModeratedOrganizations($trooper);

            return;
        }

        $trooper->trooper_assignments()->update([TrooperAssignment::IS_MODERATOR => false]);
    }

    private function assignModeratedOrganizations(Trooper $trooper): void
    {
        $assignments = $trooper->trooper_assignments()->withTrashed()->get();

        foreach ($this->permission_request->moderated_organization_ids ?? [] as $organization_id)
        {
            $assignment = $assignments->firstWhere(
                TrooperAssignment::ORGANIZATION_ID,
                (int) $organization_id
            );

            if ($assignment === null)
            {
                $assignment = new TrooperAssignment;
                $assignment->trooper_id = $trooper->id;
                $assignment->organization_id = (int) $organization_id;
            }
            elseif ($assignment->trashed())
            {
                // Granting moderation must not revive the old membership; it can
                // clash with a membership the trooper now holds in the same branch.
                $assignment->is_member = false;
                $assignment->restore();
            }

            $assignment->is_moderator = true;
            $assignment->save();
        }
    }

    /**
     * Only the moderator flag is cleared; any membership on the same
     * assignment is kept.
     */
    private function unassignModeratedOrganizations(Trooper $trooper): void
    {
        $organization_ids = $this->permission_request->unmoderated_organization_ids ?? [];

        if ($organization_ids === [])
        {
            return;
        }

        $trooper->trooper_assignments()
            ->whereIn(TrooperAssignment::ORGANIZATION_ID, $organization_ids)
            ->update([TrooperAssignment::IS_MODERATOR => false]);
    }

    /**
     * @param  array<int, array{id:int,title:string}>|null  $groups
     * @return array<int, int>
     */
    private function groupIds(?array $groups): array
    {
        return collect($groups ?? [])
            ->pluck('id')
            ->map(fn (int|string $id): int => (int) $id)
            ->all();
    }
}
