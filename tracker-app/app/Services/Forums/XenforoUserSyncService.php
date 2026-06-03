<?php

declare(strict_types=1);

namespace App\Services\Forums;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class XenforoUserSyncService
{
    private ?Collection $managed_group_ids_cache = null;

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
     * Return all intermediate group-sync state for a trooper without writing
     * anything to XenForo. Useful for dry-run inspection via the CLI.
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
        $managedGroupIds = $this->getManagedOrganizationGroupIds();
        $desiredManaged = $this->getDesiredOrganizationGroupIds($trooper);

        $base = [
            'xenforo_user_id' => $xenforoUserId,
            'managed_group_ids' => $managedGroupIds->all(),
            'current_secondary' => [],
            'current_tt_managed' => [],
            'current_preserved' => [],
            'desired_managed' => $desiredManaged->all(),
            'computed_result' => null,
            'would_send' => false,
        ];

        if ($xenforoUserId === null || $managedGroupIds->isEmpty())
        {
            return $base;
        }

        $currentSecondary = $this->fetchCurrentSecondaryGroupIds($xenforoUserId);

        if ($currentSecondary === null)
        {
            return $base;
        }

        $currentPreserved = $currentSecondary->reject(fn (int $id) => $managedGroupIds->contains($id))->values();
        $currentTTManaged = $currentSecondary->filter(fn (int $id) => $managedGroupIds->contains($id))->sort()->values();
        $desiredSorted = $desiredManaged->sort()->values();

        $is_no_op = $currentTTManaged->all() === $desiredSorted->all();
        $computed_result = $is_no_op ? null : $currentPreserved->merge($desiredManaged)->unique()->values()->all();

        return array_merge($base, [
            'current_secondary' => $currentSecondary->all(),
            'current_tt_managed' => $currentTTManaged->all(),
            'current_preserved' => $currentPreserved->all(),
            'computed_result' => $computed_result,
            'would_send' => $computed_result !== null,
        ]);
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
            $payload['secondary_group_ids'] = $secondaryGroupIds;
        }

        return $payload;
    }

    /**
     * Build the desired secondary_group_ids array, preserving non–TroopTracker-managed
     * groups and adding/removing organization groups based on the trooper's memberships.
     *
     * Returns null when TT's managed groups are already correct (no-op) or when
     * the current XenForo state cannot be safely read. Returns an array (possibly
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

        $currentSecondary = $this->fetchCurrentSecondaryGroupIds($xenforoUserId);

        if ($currentSecondary === null)
        {
            return null;
        }

        $currentPreserved = $currentSecondary->reject(fn (int $id) => $managedGroupIds->contains($id))->values();
        $desiredManaged = $this->getDesiredOrganizationGroupIds($trooper);

        $currentTTManaged = $currentSecondary->filter(fn (int $id) => $managedGroupIds->contains($id))->sort()->values();
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

        return $currentPreserved->merge($desiredManaged)->unique()->values()->all();
    }

    /**
     * Fetch and parse a XenForo user's current secondary_group_ids.
     * Returns null when the API call fails or returns an unexpected response.
     *
     * @return Collection<int,int>|null
     */
    private function fetchCurrentSecondaryGroupIds(int $xenforo_user_id): ?Collection
    {
        $user = $this->xenforo->get_user($xenforo_user_id);

        if ($user['status'] < 200 || $user['status'] >= 300 || ! is_array($user['body']))
        {
            return null;
        }

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

        return collect($rawSecondary)
            ->filter(static fn ($id) => is_numeric($id))
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * All group IDs that are managed by TroopTracker based on organization
     * mappings (active/reserve/retired) stored on organizations.
     */
    private function getManagedOrganizationGroupIds(): Collection
    {
        if ($this->managed_group_ids_cache !== null)
        {
            return $this->managed_group_ids_cache;
        }

        $this->managed_group_ids_cache = Organization::query()
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

        return $this->managed_group_ids_cache;
    }

    /**
     * Desired organization-related group IDs for a specific trooper based on
     * their membership_status in each organization and its full ancestor chain.
     */
    private function getDesiredOrganizationGroupIds(Trooper $trooper): Collection
    {
        // Two levels of parent nesting covers the full three-level hierarchy
        // (Organization → Region → Unit) without N+1 queries.
        $trooper->loadMissing(
            'organizations.parent.parent',
            'trooper_assignments.organization.parent.parent',
        );

        $this->logOrgMembershipSnapshot($trooper);

        $assignedOrgs = $trooper->trooper_assignments
            ->filter(static fn (TrooperAssignment $a) => $a->is_member)
            ->map(static fn (TrooperAssignment $a) => $a->organization)
            ->filter();

        $allOrgs = $trooper->organizations->merge($assignedOrgs)->unique('id')->values();

        return $allOrgs
            ->flatMap(function (Organization $org) use ($trooper) {
                $status = $this->resolveEffectiveOrganizationStatus($trooper, $org->pivot->membership_status ?? null);

                $ids = array_filter([$this->resolveGroupIdForStatus($org, $status)]);

                $ancestor = $org->parent;
                while ($ancestor !== null)
                {
                    $id = $this->resolveGroupIdForStatus($ancestor, $status);
                    if ($id !== null)
                    {
                        $ids[] = $id;
                    }
                    $ancestor = $ancestor->parent;
                }

                return array_values($ids);
            })
            ->unique()
            ->values();
    }

    /** Returns the XenForo group ID configured on an org for the given membership status. */
    private function resolveGroupIdForStatus(Organization $org, string $status): ?int
    {
        if ($status === 'active' && $org->xenforo_group_active_id !== null)
        {
            return (int) $org->xenforo_group_active_id;
        }

        if ($status === 'reserve' && $org->xenforo_group_reserve_id !== null)
        {
            return (int) $org->xenforo_group_reserve_id;
        }

        if ($status === 'retired' && $org->xenforo_group_retired_id !== null)
        {
            return (int) $org->xenforo_group_retired_id;
        }

        return null;
    }

    private function resolveEffectiveOrganizationStatus(Trooper $trooper, mixed $organizationStatus): string
    {
        $status = $organizationStatus instanceof MembershipStatus
            ? $organizationStatus->value
            : strtolower(is_string($organizationStatus) ? $organizationStatus : MembershipStatus::ACTIVE->value);

        $globalStatus = $trooper->membership_status instanceof MembershipStatus
            ? $trooper->membership_status
            : MembershipStatus::tryFrom((string) $trooper->membership_status);

        if ($globalStatus === MembershipStatus::RETIRED && in_array($status, [
            MembershipStatus::ACTIVE->value,
            MembershipStatus::RESERVE->value,
        ], true))
        {
            return MembershipStatus::RETIRED->value;
        }

        if ($globalStatus !== MembershipStatus::ACTIVE && $globalStatus !== MembershipStatus::RETIRED)
        {
            return MembershipStatus::NONE->value;
        }

        return $status;
    }

    /** Write org + assignment membership state to the application log. */
    private function logOrgMembershipSnapshot(Trooper $trooper): void
    {
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

        return $trooper->organizations
            ->filter(static function ($organization) {
                $status = $organization->pivot->membership_status ?? null;

                if (! is_string($status))
                {
                    return false;
                }

                return in_array(strtolower($status), ['active', 'reserve'], true);
            })
            ->map(static fn ($organization) => (string) $organization->name)
            ->values()
            ->unique()
            ->sort()
            ->values();
    }
}
