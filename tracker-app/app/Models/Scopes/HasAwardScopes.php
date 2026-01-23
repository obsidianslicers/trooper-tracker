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
     * Scope a query to only include awards visible to a given trooper.
     *
     * An award is visible if it is global (no organization) or if the trooper is a
     * member of an organization within the award's organizational hierarchy.
     *
     * @param Builder $query The Eloquent query builder.
     * @param Trooper $trooper The trooper whose visibility is being checked.
     * @return Builder<self>
     */
    public function scopeVisibleTo(Builder $query, Trooper $trooper): Builder
    {
        $query->where(function ($outer) use ($trooper)
        {
            $outer->whereExists(function ($sub) use ($trooper)
            {
                $sub->select(DB::raw(1))
                    ->from('tt_trooper_assignments as ta_assign')
                    ->join('tt_organizations as org_assign', 'ta_assign.organization_id', '=', 'org_assign.id')
                    ->join('tt_organizations as org_award', 'tt_awards.organization_id', '=', 'org_award.id')
                    ->where('ta_assign.trooper_id', $trooper->id)
                    ->where('ta_assign.is_member', true)
                    ->whereRaw('org_assign.node_path LIKE CONCAT(org_award.node_path, "%")');
            })->orWhereNull('tt_awards.organization_id');
        });

        return $query;
    }

    /**
     * Scope a query to only include awards that can be managed by a given moderator.
     *
     * An award is moderated if its organization falls within the moderator's
     * assigned organizational hierarchy.
     *
     * @param Builder $query The Eloquent query builder.
     * @param Trooper $trooper The moderator to filter by.
     * @return Builder
     */
    public function scopeModeratedBy(Builder $query, Trooper $trooper): Builder
    {
        if ($trooper->is_administrator)
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