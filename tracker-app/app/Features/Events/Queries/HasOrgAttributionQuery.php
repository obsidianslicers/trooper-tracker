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
                $q->where('tt_event_troopers.organization_id', $org_id)
                    ->orWhere(function ($q) use ($org_id) {
                        $q->whereNull('tt_event_troopers.organization_id')
                            ->whereRaw('JSON_CONTAINS(tt_event_troopers.costume_organization_ids, ?)', [json_encode($org_id)]);
                    });
            });
        }
        elseif (!empty($accessible_org_ids))
        {
            $encoded = json_encode($accessible_org_ids);
            $q->where(function ($q) use ($accessible_org_ids, $encoded) {
                $q->whereExists(function ($q) use ($accessible_org_ids) {
                    $q->from('tt_organizations as et_org')
                        ->whereColumn('et_org.id', 'tt_event_troopers.organization_id')
                        ->whereIn(
                            DB::raw("CAST(SUBSTRING_INDEX(et_org.node_path, ':', 1) AS UNSIGNED)"),
                            $accessible_org_ids
                        );
                })
                    ->orWhereRaw('JSON_OVERLAPS(tt_event_troopers.costume_organization_ids, ?)', [$encoded]);
            });
        }
    }
}
