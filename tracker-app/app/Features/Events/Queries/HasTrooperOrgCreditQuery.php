<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Models\Organization;
use Illuminate\Support\Facades\DB;

/**
 * Determines whether an `tt_event_troopers` row should count toward a given organization
 * scope. A shift counts when either:
 *
 * - Roster: the attending trooper is currently a roster member (is_member) of one of the given
 *   organizations, and the credit was given to that trooper's own organization or any ancestor
 *   of it (e.g. credit logged at the garrison level still counts for a squad member).
 * - Direct credit: the credited organization itself is inside the scope. This keeps historical
 *   credit counting after a trooper leaves a club, since it doesn't depend on membership.
 */
trait HasTrooperOrgCreditQuery
{
    protected function applyTrooperOrgCredit(mixed $q, array $roster_org_ids, array $accessible_root_ids): void
    {
        if (empty($roster_org_ids) && empty($accessible_root_ids))
        {
            return;
        }

        $scope_org_ids = !empty($roster_org_ids)
            ? $roster_org_ids
            : $this->resolveRootSubtreeIds($accessible_root_ids);

        $q->where(function ($q) use ($roster_org_ids, $accessible_root_ids, $scope_org_ids)
        {
            $q->where(
                fn ($q) => $this->whereRosterCredit($q, $roster_org_ids, $accessible_root_ids)
            )
                ->orWhere(fn ($q) => $this->whereDirectCredit($q, $scope_org_ids));
        });
    }

    /**
     * Restricts to rows credited directly to one of the given organizations, regardless of the
     * trooper's current memberships.
     */
    protected function whereDirectCredit(mixed $q, array $scope_org_ids): void
    {
        if (empty($scope_org_ids))
        {
            $q->whereRaw('1 = 0');

            return;
        }

        $scope_json = json_encode(array_values(array_map('intval', $scope_org_ids)));

        $q->where(function ($q) use ($scope_json, $scope_org_ids)
        {
            $q->where(function ($q) use ($scope_json)
            {
                $this->whereHasCostumeOrganizationCredit($q);
                $q->whereRaw($this->costumeCreditOverlapsSql(), [$scope_json]);
            })
                ->orWhere(function ($q) use ($scope_org_ids)
                {
                    $this->whereNoCostumeOrganizationCredit($q);
                    $q->whereIn('tt_event_troopers.organization_id', $scope_org_ids);
                });
        });
    }

    private function costumeCreditOverlapsSql(): string
    {
        return DB::getDriverName() === 'sqlite'
            ? 'EXISTS (SELECT 1 '.
                'FROM json_each(tt_event_troopers.costume_organization_ids) AS credit '.
                'WHERE credit.value IN (SELECT value FROM json_each(?)))'
            : 'JSON_OVERLAPS(tt_event_troopers.costume_organization_ids, ?)';
    }

    private function whereRosterCredit(
        mixed $q,
        array $roster_org_ids,
        array $accessible_root_ids
    ): void {
        $q->whereExists(function ($sub) use ($roster_org_ids, $accessible_root_ids)
        {
            $sub->select(DB::raw(1))
                ->from('tt_trooper_assignments as ta_credit')
                ->join('tt_organizations as trooper_org', 'ta_credit.organization_id', '=', 'trooper_org.id')
                ->whereColumn('ta_credit.trooper_id', 'tt_event_troopers.trooper_id')
                ->where('ta_credit.is_member', true);

            if (!empty($roster_org_ids))
            {
                $sub->whereIn('trooper_org.id', $roster_org_ids);
            }
            else
            {
                $sub->whereIn(
                    DB::raw('CAST(SUBSTRING_INDEX(trooper_org.node_path, \':\', 1) AS UNSIGNED)'),
                    $accessible_root_ids
                );
            }

            // trooper_org.node_path is a colon-delimited chain of ancestor ids (e.g. "501:42:").
            // Turning it into a JSON array lets us test, in one expression, whether any id the
            // event was credited to (via costume_organization_ids) is the trooper's own org or
            // one of its ancestors.
            $ancestor_ids_json = "CONCAT('[', REPLACE(TRIM(TRAILING ':' FROM trooper_org.node_path), ':', ','), ']')";

            $sub->where(function ($sub) use ($ancestor_ids_json)
            {
                $sub->where(function ($sub) use ($ancestor_ids_json)
                {
                    $this->whereHasCostumeOrganizationCredit($sub);
                    $sub->whereRaw("JSON_OVERLAPS($ancestor_ids_json, tt_event_troopers.costume_organization_ids)");
                })
                    ->orWhere(function ($sub)
                    {
                        $this->whereNoCostumeOrganizationCredit($sub);
                        $sub->whereRaw(
                            '(trooper_org.node_path LIKE CONCAT(tt_event_troopers.organization_id, \':%\') '.
                            'OR trooper_org.node_path LIKE CONCAT(\'%:\', tt_event_troopers.organization_id, \':%\'))'
                        );
                    });
            });
        });
    }

    /**
     * Expands root club ids to every organization id beneath them.
     *
     * @param  array<int>  $root_ids
     * @return array<int>
     */
    protected function resolveRootSubtreeIds(array $root_ids): array
    {
        return $this->resolveSubtreeIdsByPathPrefix(array_map(
            fn ($root_id) => ((int) $root_id).Organization::NODE_PATH_SEP,
            $root_ids
        ));
    }

    /**
     * Resolves an organization and all of its descendants to a flat list of ids, so a single
     * club selection also captures every squad/unit nested beneath it.
     *
     * @return array<int>
     */
    protected function resolveOrgSubtreeIds(?Organization $organization): array
    {
        return $organization
            ? $this->resolveSubtreeIdsByPathPrefix([$organization->node_path])
            : [];
    }

    /**
     * @param  array<int, string>  $node_path_prefixes
     * @return array<int>
     */
    private function resolveSubtreeIdsByPathPrefix(array $node_path_prefixes): array
    {
        if (empty($node_path_prefixes))
        {
            return [];
        }

        return Organization::query()
            ->where(function ($q) use ($node_path_prefixes)
            {
                foreach ($node_path_prefixes as $prefix)
                {
                    $q->orWhere(fn ($q) => $q->withinNodePath($prefix));
                }
            })
            ->pluck(Organization::ID)
            ->all();
    }

    private function whereHasCostumeOrganizationCredit(mixed $q): void
    {
        $json_path = "REPLACE(tt_event_troopers.costume_organization_ids, ' ', '')";

        $q->whereNotNull('tt_event_troopers.costume_organization_ids')
            ->whereRaw($json_path.' != ?', ['[]']);
    }

    private function whereNoCostumeOrganizationCredit(mixed $q): void
    {
        $q->where(function ($q)
        {
            $q->whereNull('tt_event_troopers.costume_organization_ids')
                ->orWhereRaw(
                    "REPLACE(CAST(tt_event_troopers.costume_organization_ids AS CHAR), ' ', '') = ?",
                    ['[]']
                );
        });
    }
}
