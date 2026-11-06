<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait HasTrooperCostumeScopes
{
    /**
     * Scope to trooper costumes a moderator may approve or revoke.
     *
     * A moderator qualifies when one of their moderated units covers one of the
     * trooper's memberships and sits in the same club tree as the costume. Nobody
     * reviews their own costumes, administrators included.
     *
     * @param  Builder<TrooperCostume>  $query
     * @return Builder<TrooperCostume>
     */
    public function scopeModeratedBy(Builder $query, Trooper $moderator): Builder
    {
        $query->where($query->qualifyColumn(TrooperCostume::TROOPER_ID), '!=', $moderator->id);

        if ($moderator->is_administrator)
        {
            return $query;
        }

        return $query->whereExists(function ($sub) use ($moderator): void
        {
            $sub->select(DB::raw(1))
                ->from('tt_trooper_assignments as ta_moderator')
                ->join('tt_organizations as org_moderator', 'ta_moderator.organization_id', '=', 'org_moderator.id')
                ->join('tt_organization_costumes as oc_review', 'oc_review.id', '=', 'tt_trooper_costumes.organization_costume_id')
                ->join('tt_organizations as org_costume', 'oc_review.organization_id', '=', 'org_costume.id')
                ->where('ta_moderator.trooper_id', $moderator->id)
                ->where('ta_moderator.is_moderator', true)
                ->whereNull('ta_moderator.deleted_at')
                ->where(function ($tree): void
                {
                    $tree->whereRaw('org_moderator.node_path LIKE CONCAT(org_costume.node_path, "%")')
                        ->orWhereRaw('org_costume.node_path LIKE CONCAT(org_moderator.node_path, "%")');
                })
                ->whereExists(function ($member): void
                {
                    $member->select(DB::raw(1))
                        ->from('tt_trooper_assignments as ta_member')
                        ->join('tt_organizations as org_member', 'ta_member.organization_id', '=', 'org_member.id')
                        ->whereColumn('ta_member.trooper_id', 'tt_trooper_costumes.trooper_id')
                        ->where('ta_member.is_member', true)
                        ->whereNull('ta_member.deleted_at')
                        ->whereRaw('org_member.node_path LIKE CONCAT(org_moderator.node_path, "%")');
                });
        });
    }
}
