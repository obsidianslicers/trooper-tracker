<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Models\Organization;
use Illuminate\Support\Facades\DB;

/**
 * Determines whether an `tt_event_troopers` row should count toward a given organization
 * scope, based on the attending trooper's own roster placement rather than how the event
 * itself was tagged. A shift only counts when the trooper is a roster member (is_member)
 * of one of the given organizations, and the credit on the event_trooper record was given
 * to that trooper's own organization or to any ancestor of it (e.g. credit logged at the
 * garrison level still counts for a squad member). The specific org an event was credited
 * to doesn't matter, only whether the credit reaches the trooper's place in the hierarchy.
 */
trait HasTrooperOrgCreditQuery
{
    protected function applyTrooperOrgCredit(mixed $q, array $roster_org_ids, array $accessible_root_ids): void
    {
        if (empty($roster_org_ids) && empty($accessible_root_ids))
        {
            return;
        }

        $q->whereExists(function ($sub) use ($roster_org_ids, $accessible_root_ids) {
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

            $sub->where(function ($sub) use ($ancestor_ids_json) {
                $sub->where(function ($sub) use ($ancestor_ids_json) {
                    $this->whereHasCostumeOrganizationCredit($sub);
                    $sub->whereRaw("JSON_OVERLAPS($ancestor_ids_json, tt_event_troopers.costume_organization_ids)");
                })
                    ->orWhere(function ($sub) {
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
     * Resolves an organization and all of its descendants to a flat list of ids, so a single
     * club selection also captures every squad/unit nested beneath it.
     *
     * @return array<int>
     */
    protected function resolveOrgSubtreeIds(?Organization $organization): array
    {
        if (!$organization)
        {
            return [];
        }

        return Organization::query()
            ->where(Organization::NODE_PATH, 'like', $organization->node_path.'%')
            ->pluck('id')
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
        $q->where(function ($q) {
            $q->whereNull('tt_event_troopers.costume_organization_ids')
                ->orWhereRaw(
                    "REPLACE(CAST(tt_event_troopers.costume_organization_ids AS CHAR), ' ', '') = ?",
                    ['[]']
                );
        });
    }
}
