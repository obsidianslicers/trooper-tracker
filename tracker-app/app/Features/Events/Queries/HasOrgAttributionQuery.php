<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Models\Organization;
use Illuminate\Support\Facades\DB;

trait HasOrgAttributionQuery
{
    protected function applyOrgAttribution(
        mixed $q,
        ?Organization $org,
        array $accessible_org_ids
    ): void {
        if ($org)
        {
            $org_id = $org->id;
            $q->where(function ($q) use ($org_id) {
                $q->where(function ($q) use ($org_id) {
                    $this->whereJsonArrayContainsOrganization($q, $org_id);
                })
                    ->orWhere(function ($q) use ($org_id) {
                        $this->whereNoCostumeOrganizationCredit($q);
                        $q->where('tt_event_troopers.organization_id', $org_id);
                    });
            });
        }
        elseif (!empty($accessible_org_ids))
        {
            $q->where(function ($q) use ($accessible_org_ids) {
                $q->where(function ($q) use ($accessible_org_ids) {
                    foreach ($accessible_org_ids as $org_id)
                    {
                        $q->orWhere(function ($q) use ($org_id) {
                            $this->whereJsonArrayContainsOrganization($q, (int) $org_id);
                        });
                    }
                })
                    ->orWhere(function ($q) use ($accessible_org_ids) {
                        $this->whereNoCostumeOrganizationCredit($q);
                        $q->whereExists(function ($q) use ($accessible_org_ids) {
                            $q->from('tt_organizations as et_org')
                                ->whereColumn('et_org.id', 'tt_event_troopers.organization_id')
                                ->whereIn(
                                    DB::raw("CAST(SUBSTRING_INDEX(et_org.node_path, ':', 1) AS UNSIGNED)"),
                                    $accessible_org_ids
                                );
                        });
                    });
            });
        }
    }

    private function whereJsonArrayContainsOrganization(mixed $q, int $org_id): void
    {
        $json_path = "REPLACE(tt_event_troopers.costume_organization_ids, ' ', '')";

        $q->whereJsonContains('tt_event_troopers.costume_organization_ids', $org_id)
            ->orWhereRaw($json_path.' = ?', ['['.$org_id.']'])
            ->orWhereRaw($json_path.' LIKE ?', ['['.$org_id.',%'])
            ->orWhereRaw($json_path.' LIKE ?', ['%,'.$org_id.',%'])
            ->orWhereRaw($json_path.' LIKE ?', ['%,'.$org_id.']']);
    }

    private function whereNoCostumeOrganizationCredit(mixed $q): void
    {
        $q->where(function ($q) {
            $q->whereNull('tt_event_troopers.costume_organization_ids')
                ->orWhereRaw("REPLACE(CAST(tt_event_troopers.costume_organization_ids AS CHAR), ' ', '') = ?", ['[]']);
        });
    }
}
