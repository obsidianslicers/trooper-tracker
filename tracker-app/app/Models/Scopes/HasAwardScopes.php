<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\Trooper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Trait containing local scopes for the Award model.
 */
trait HasAwardScopes
{
    /**
     * Scope a query to only include notices that can be managed by a given moderator.
     *
     * A notice is moderated if its organization falls within the moderator's
     * assigned organizational hierarchy.
     *
     * @param Builder $query The Eloquent query builder.
     * @param Trooper $trooper The moderator to filter by.
     * @return Builder
     */
    protected function scopeModeratedBy(Builder $query, Trooper $trooper): Builder
    {
        if ($trooper->isAdministrator())
        {
            return $query;
        }

        return $query->whereExists(function ($sub) use ($trooper)
        {
            $sub->select(DB::raw(1))
                ->from('tt_trooper_assignments as ta_moderator')
                ->join('tt_organizations as org_moderator', 'ta_moderator.organization_id', '=', 'org_moderator.id')
                ->join('tt_organizations as org_notice', 'tt_awards.organization_id', '=', 'org_notice.id')
                ->where('ta_moderator.trooper_id', $trooper->id)
                ->where('ta_moderator.is_moderator', true)
                ->whereRaw('org_notice.node_path LIKE CONCAT(org_moderator.node_path, "%")');
        });
    }
}