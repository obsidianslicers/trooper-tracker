<?php

declare(strict_types=1);

namespace App\Messages\Troopers\PageData\Permissions;

use App\Facades\TroopTrackerFacade;
use App\Messages\Troopers\Queries\Permissions\GetTrooperForumGroupIds;
use App\Messages\Troopers\Resources\PermissionRequestsCollection;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Services\Forums\XenforoService;
use Hyperdrive\Message;
use Illuminate\Database\Eloquent\Builder;

/**
 * A trooper's current role, moderated organizations, and forum groups, used to
 * pre-fill the permission request form once a trooper is picked.
 *
 * @method static array<string, mixed> call(Trooper $trooper)
 */
final class LookupTrooperPermissionsPageData extends Message
{
    public function __construct(private readonly Trooper $trooper) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(XenforoService $xenforo): array
    {
        $has_forum_account = TroopTrackerFacade::isXenforoIntegrationConfigured()
            && $xenforo->resolve_user_id_for_trooper($this->trooper->id) !== null;

        $forum_group_ids = GetTrooperForumGroupIds::call(trooper: $this->trooper);
        $moderated_ids = $this->getModeratedOrganizationIds();

        return [
            'trooper' => [
                'id' => $this->trooper->id,
                'display_name' => $this->trooper->display_name,
                'legal_name' => $this->trooper->legal_name,
                'membership_role' => $this->trooper->membership_role?->value,
                'membership_role_label' => PermissionRequestsCollection::roleLabel(
                    $this->trooper->membership_role
                ),
            ],
            'moderated_organization_ids' => $moderated_ids,
            'covered_organization_ids' => $this->getCoveredOrganizationIds($moderated_ids),
            'has_forum_account' => $has_forum_account,
            'forum_group_ids' => $forum_group_ids ?? [],
        ];
    }

    /**
     * Moderating an organization covers everything beneath it, so expand the
     * moderated organizations to their full subtrees.
     *
     * @param  array<int, int>  $moderated_ids
     * @return array<int, int>
     */
    private function getCoveredOrganizationIds(array $moderated_ids): array
    {
        if ($moderated_ids === [])
        {
            return [];
        }

        $node_paths = Organization::query()
            ->whereIn(Organization::ID, $moderated_ids)
            ->pluck(Organization::NODE_PATH);

        return Organization::query()
            ->where(function (Builder $query) use ($node_paths): void
            {
                foreach ($node_paths as $node_path)
                {
                    $query->orWhere(Organization::NODE_PATH, 'like', $node_path.'%');
                }
            })
            ->pluck(Organization::ID)
            ->map(fn (int|string $id): int => (int) $id)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function getModeratedOrganizationIds(): array
    {
        return $this->trooper->trooper_assignments()
            ->where(TrooperAssignment::IS_MODERATOR, true)
            ->pluck(TrooperAssignment::ORGANIZATION_ID)
            ->map(fn (int|string $id): int => (int) $id)
            ->all();
    }
}
