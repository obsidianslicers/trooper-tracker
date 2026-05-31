<?php

declare(strict_types=1);

namespace App\Services\Forums;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class XenforoUserSyncService
{
    public function __construct(private readonly XenforoService $xenforo) {}

    /**
     * Sync a single trooper to their linked XenForo user, if any.
     */
    public function syncTrooper(Trooper $trooper): void
    {
        $xenforoUserId = $this->xenforo->resolve_user_id_for_trooper($trooper->id);

        if ($xenforoUserId === null)
        {
            return;
        }

        if (! $trooper->is_active)
        {
            return;
        }

        $payload = $this->buildUserPayload($trooper, $xenforoUserId);

        Log::info('XenForo sync payload built', [
            'trooper_id' => $trooper->id,
            'xenforo_user_id' => $xenforoUserId,
            'payload_keys' => array_keys($payload),
            'secondary_group_ids' => $payload['secondary_group_ids'] ?? null,
        ]);

        if (empty($payload))
        {
            return;
        }

        $result = $this->xenforo->update_user($xenforoUserId, $payload);

        if ($result['status'] < 200 || $result['status'] >= 300)
        {
            Log::warning('Failed to sync trooper to XenForo user', [
                'trooper_id' => $trooper->id,
                'xenforo_user_id' => $xenforoUserId,
                'status' => $result['status'],
                'body' => $result['body'],
            ]);
        }
    }

    /**
     * Build the XenForo user update payload for a trooper.
     *
     * - custom_fields[trackerid]: TroopTracker trooper ID
     * - custom_fields[fullname]:   Trooper legal name (fallback to display name)
     * - custom_fields[tkid]:       Formatted member ID with prefix, e.g. "TK52233"
     * - custom_fields[organizations]: Comma-separated list of active org names
     * - secondary_group_ids: merged secondary groups including TT-managed org groups
     *
     * @return array<string,mixed>
     */
    private function buildUserPayload(Trooper $trooper, int $xenforoUserId): array
    {
        $customFields = [];

        $customFields['trackerid'] = (string) $trooper->id;

        $fullName = trim((string) ($trooper->legal_name ?? ''));
        if ($fullName === '')
        {
            $fullName = trim((string) ($trooper->display_name ?? ''));
        }
        if ($fullName !== '')
        {
            $customFields['fullname'] = $fullName;
        }

        $tkid = $this->resolveForumDisplayTkId($trooper);
        if ($tkid !== null)
        {
            $customFields['tkid'] = $tkid;
        }

        $orgNames = $this->getActiveOrganizationNames($trooper);
        if ($orgNames->isNotEmpty())
        {
            $customFields['organizations'] = $orgNames->implode(', ');
        }

        $payload = [];
        foreach ($customFields as $field => $value)
        {
            $payload["custom_fields[{$field}]"] = $value;
        }

        $secondaryGroupIds = $this->buildSecondaryGroupIds($trooper, $xenforoUserId);

        // null  → TT's managed groups are unchanged; skip to avoid touching external groups.
        // []    → TT wants zero secondary groups (all managed groups removed, no external groups).
        // [...]  → explicit desired list merging TT-managed + preserved external groups.
        if ($secondaryGroupIds !== null)
        {
            // Let the HTTP client encode this as a form array
            // (secondary_group_ids[0]=..., etc.), which XenForo
            // will treat as an array input.
            $payload['secondary_group_ids'] = $secondaryGroupIds;
        }

        return $payload;
    }

    /**
     * Build the desired secondary_group_ids array, preserving non–TroopTracker-managed
     * groups and adding/removing organization groups based on the trooper's
     * organization memberships and the XenForo group IDs stored on each
     * organization (active, reserve, retired).
     *
     * Returns null when TT's managed groups are already correct in XenForo (no-op),
     * or when the current state cannot be safely read. Returns an array (possibly
     * empty) only when TT needs to make an actual change.
     *
     * @return array<int,int>|null
     */
    private function buildSecondaryGroupIds(Trooper $trooper, int $xenforoUserId): ?array
    {
        $managedGroupIds = $this->getManagedOrganizationGroupIds();

        if ($managedGroupIds->isEmpty())
        {
            return null;
        }

        $user = $this->xenforo->get_user($xenforoUserId);

        if ($user['status'] < 200 || $user['status'] >= 300 || ! is_array($user['body']))
        {
            return null;
        }

        $body = $user['body'];

        // XenForo typically nests the user object under a 'user' key.
        $userData = is_array($body['user'] ?? null) ? $body['user'] : $body;

        $rawSecondary = $userData['secondary_group_ids'] ?? [];

        if (is_string($rawSecondary))
        {
            $rawSecondary = array_filter(array_map('trim', explode(',', $rawSecondary)), static fn ($v) => $v !== '');
        }

        $currentSecondary = collect($rawSecondary)
            ->filter(static fn ($id) => is_numeric($id))
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values();

        $currentPreserved = $currentSecondary
            ->reject(function (int $id) use ($managedGroupIds) {
                return $managedGroupIds->contains($id);
            })
            ->values();

        $desiredManaged = $this->getDesiredOrganizationGroupIds($trooper);

        // Only write secondary_group_ids when TT's managed portion has actually changed.
        // Returning null skips the field entirely, leaving any externally-granted groups
        // (e.g. manually granted by a XenForo admin) untouched.
        $currentTTManaged = $currentSecondary
            ->filter(fn (int $id) => $managedGroupIds->contains($id))
            ->sort()
            ->values();

        $desiredSorted = $desiredManaged->sort()->values();

        Log::info('XenForo group calculation', [
            'trooper_id' => $trooper->id,
            'managed_group_ids' => $managedGroupIds->all(),
            'current_secondary' => $currentSecondary->all(),
            'current_tt_managed' => $currentTTManaged->all(),
            'current_preserved' => $currentPreserved->all(),
            'desired_managed' => $desiredManaged->all(),
            'no_op' => $currentTTManaged->all() === $desiredSorted->all(),
        ]);

        if ($currentTTManaged->all() === $desiredSorted->all())
        {
            return null;
        }

        return $currentPreserved
            ->merge($desiredManaged)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Return all intermediate group-sync state for a trooper without writing
     * anything to XenForo. Useful for debugging / dry-run inspection.
     *
     * @return array{
     *   xenforo_user_id: int|null,
     *   managed_group_ids: int[],
     *   current_secondary: int[],
     *   current_tt_managed: int[],
     *   current_preserved: int[],
     *   desired_managed: int[],
     *   computed_result: int[]|null,
     *   would_send: bool,
     * }
     */
    public function debugSync(Trooper $trooper): array
    {
        $xenforoUserId = $this->xenforo->resolve_user_id_for_trooper($trooper->id);

        $empty = [
            'xenforo_user_id' => $xenforoUserId,
            'managed_group_ids' => [],
            'current_secondary' => [],
            'current_tt_managed' => [],
            'current_preserved' => [],
            'desired_managed' => [],
            'computed_result' => null,
            'would_send' => false,
        ];

        if ($xenforoUserId === null)
        {
            return $empty;
        }

        $managedGroupIds = $this->getManagedOrganizationGroupIds();

        if ($managedGroupIds->isEmpty())
        {
            $empty['desired_managed'] = $this->getDesiredOrganizationGroupIds($trooper)->all();

            return $empty;
        }

        $user = $this->xenforo->get_user($xenforoUserId);

        $apiOk = $user['status'] >= 200 && $user['status'] < 300 && is_array($user['body']);

        $currentSecondary = collect();

        if ($apiOk)
        {
            $body = $user['body'];
            $userData = is_array($body['user'] ?? null) ? $body['user'] : $body;

            $rawSecondary = $userData['secondary_group_ids'] ?? [];

            if (is_string($rawSecondary))
            {
                $rawSecondary = array_filter(
                    array_map('trim', explode(',', $rawSecondary)),
                    static fn ($v) => $v !== ''
                );
            }

            $currentSecondary = collect($rawSecondary)
                ->filter(static fn ($id) => is_numeric($id))
                ->map(static fn ($id) => (int) $id)
                ->unique()
                ->values();
        }

        $currentPreserved = $currentSecondary
            ->reject(fn (int $id) => $managedGroupIds->contains($id))
            ->values();

        $currentTTManaged = $currentSecondary
            ->filter(fn (int $id) => $managedGroupIds->contains($id))
            ->sort()
            ->values();

        $desiredManaged = $this->getDesiredOrganizationGroupIds($trooper);
        $desiredSorted = $desiredManaged->sort()->values();

        $noOp = ! $apiOk || ($currentTTManaged->all() === $desiredSorted->all());

        $computedResult = $noOp
            ? null
            : $currentPreserved->merge($desiredManaged)->unique()->values()->all();

        return [
            'xenforo_user_id' => $xenforoUserId,
            'managed_group_ids' => $managedGroupIds->all(),
            'current_secondary' => $currentSecondary->all(),
            'current_tt_managed' => $currentTTManaged->all(),
            'current_preserved' => $currentPreserved->all(),
            'desired_managed' => $desiredManaged->all(),
            'computed_result' => $computedResult,
            'would_send' => $computedResult !== null,
        ];
    }

    /**
     * All group IDs that are managed by TroopTracker based on organization
     * mappings (active/reserve/retired) stored on organizations.
     */
    private function getManagedOrganizationGroupIds(): Collection
    {
        static $cache = null;

        if ($cache !== null)
        {
            return $cache;
        }

        $ids = Organization::query()
            ->select([
                Organization::XENFORO_GROUP_ACTIVE_ID,
                Organization::XENFORO_GROUP_RESERVE_ID,
                Organization::XENFORO_GROUP_RETIRED_ID,
            ])
            ->get()
            ->flatMap(static function (Organization $org) {
                return [
                    $org->xenforo_group_active_id,
                    $org->xenforo_group_reserve_id,
                    $org->xenforo_group_retired_id,
                ];
            })
            ->filter(static fn ($id) => ! is_null($id))
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values();

        $cache = $ids;

        return $cache;
    }

    /**
     * Desired organization-related group IDs for a specific trooper based on
     * their membership_status in each organization.
     */
    private function getDesiredOrganizationGroupIds(Trooper $trooper): Collection
    {
        // Two levels of parent nesting covers the full three-level hierarchy
        // (Organization → Region → Unit) without N+1 queries.
        $trooper->loadMissing(
            'organizations.parent.parent',
            'trooper_assignments.organization.parent.parent',
        );

        Log::info('XenForo org membership snapshot', [
            'trooper_id' => $trooper->id,
            'organizations' => $trooper->organizations->map(static function (Organization $org) {
                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'pivot_status' => $org->pivot->membership_status ?? null,
                    'org_groups' => [
                        'active' => $org->xenforo_group_active_id,
                        'reserve' => $org->xenforo_group_reserve_id,
                        'retired' => $org->xenforo_group_retired_id,
                    ],
                ];
            })->all(),
            'assignments' => $trooper->trooper_assignments->map(static function (TrooperAssignment $assignment) {
                return [
                    'org_id' => $assignment->organization_id,
                    'org_name' => optional($assignment->organization)->name,
                    'type' => optional($assignment->organization)->type,
                    'is_member' => $assignment->is_member,
                    'is_moderator' => $assignment->is_moderator,
                ];
            })->all(),
        ]);

        $orgIdsFromMembership = $trooper->organizations->pluck('id')->all();

        // Also consider organizations from TrooperAssignment where the trooper
        // is marked as a member. This is typically used for squad/region
        // assignments in the new system.
        $assignedOrgs = $trooper->trooper_assignments
            ->filter(static fn (TrooperAssignment $a) => $a->is_member)
            ->map(static fn (TrooperAssignment $a) => $a->organization)
            ->filter();

        $allOrgs = $trooper->organizations
            ->merge($assignedOrgs)
            ->unique('id')
            ->values();

        return $allOrgs
            ->flatMap(static function (Organization $org) {
                $status = $org->pivot->membership_status ?? null;

                // Fall back to treating assignment membership as ACTIVE when
                // we don't have a pivot membership_status (e.g. for
                // TrooperAssignment-sourced organizations).
                if (! is_string($status))
                {
                    $status = 'active';
                }

                $status = strtolower($status);

                $ids = [];

                // First, use group IDs defined directly on this organization
                if ($status === 'active' && ! is_null($org->xenforo_group_active_id))
                {
                    $ids[] = (int) $org->xenforo_group_active_id;
                }
                elseif ($status === 'reserve' && ! is_null($org->xenforo_group_reserve_id))
                {
                    $ids[] = (int) $org->xenforo_group_reserve_id;
                }
                elseif ($status === 'retired' && ! is_null($org->xenforo_group_retired_id))
                {
                    $ids[] = (int) $org->xenforo_group_retired_id;
                }

                // Walk every ancestor (region, primary club) and collect their
                // XenForo group IDs for the same membership status. The parent
                // chain is already eager-loaded so this does not trigger
                // additional queries.
                $ancestor = $org->parent;

                while ($ancestor !== null)
                {
                    if ($status === 'active' && ! is_null($ancestor->xenforo_group_active_id))
                    {
                        $ids[] = (int) $ancestor->xenforo_group_active_id;
                    }
                    elseif ($status === 'reserve' && ! is_null($ancestor->xenforo_group_reserve_id))
                    {
                        $ids[] = (int) $ancestor->xenforo_group_reserve_id;
                    }
                    elseif ($status === 'retired' && ! is_null($ancestor->xenforo_group_retired_id))
                    {
                        $ids[] = (int) $ancestor->xenforo_group_retired_id;
                    }

                    $ancestor = $ancestor->parent;
                }

                return $ids;
            })
            ->unique()
            ->values();
    }

    /** Returns the formatted TKID to sync to XenForo (e.g. "TK52233"), or null. */
    private function resolveForumDisplayTkId(Trooper $trooper): ?string
    {
        $trooper->loadMissing([
            'trooper_costumes.organization_costume',
            'organizations',
        ]);

        if ($trooper->trooper_costumes->isEmpty())
        {
            return null;
        }

        $display_costume = $this->resolveDisplayCostume($trooper);

        if ($display_costume === null || $display_costume->organization_costume === null)
        {
            return null;
        }

        $prefix = $display_costume->organization_costume->prefix;
        $org_id = $display_costume->organization_costume->organization_id;

        $org = $trooper->organizations->firstWhere('id', $org_id);
        $identifier = $org?->pivot?->identifier ?? null;

        if ($prefix === null || $prefix === '' || $identifier === null || $identifier === '')
        {
            return null;
        }

        return $prefix.$identifier;
    }

    private function resolveDisplayCostume(Trooper $trooper): ?TrooperCostume
    {
        if ($trooper->display_costume_id !== null)
        {
            return $trooper->trooper_costumes
                ->firstWhere(TrooperCostume::ID, $trooper->display_costume_id);
        }

        return $trooper->trooper_costumes
            ->filter(fn (TrooperCostume $tc) => ! is_null($tc->organization_costume?->prefix))
            ->sortBy(fn (TrooperCostume $tc) => $tc->organization_costume->prefix)
            ->first();
    }

    /**
     * Get names of organizations where the trooper has an active-like membership
     * based on the pivot membership_status for the many-to-many organizations()
     * relationship.
     *
     * @return Collection<int,string>
     */
    private function getActiveOrganizationNames(Trooper $trooper): Collection
    {
        $trooper->loadMissing('organizations');

        // We want organizations where the pivot membership_status is ACTIVE or RESERVE.
        return $trooper->organizations
            ->filter(static function ($organization) {
                $status = $organization->pivot->membership_status ?? null;

                if (! is_string($status))
                {
                    return false;
                }

                $status = strtolower($status);

                return in_array($status, ['active', 'reserve'], true);
            })
            ->map(static function ($organization) {
                return (string) $organization->name;
            })
            ->values()
            ->unique()
            ->sort()
            ->values();
    }
}
