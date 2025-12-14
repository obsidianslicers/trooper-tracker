<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Trait containing local scopes for the Trooper model.
 */
trait HasTrooperScopes
{
    /**
     * Scope a query to find a trooper by their username.
     *
     * @param Builder<Trooper> $query The Eloquent query builder.
     * @param string $username The username to search for.
     * @return Builder<Trooper>
     */
    public function scopeByUsername(Builder $query, string $username): Builder
    {
        return $query->where(self::USERNAME, $username);
    }

    /**
     * Scope a query to find all troopers with a pending membership status.
     *
     * @param Builder<Trooper> $query The Eloquent query builder.
     * @return Builder<Trooper>
     */
    public function scopePendingApprovals(Builder $query): Builder
    {
        $with = [
            'trooper_assignments.organization.parent',
            'trooper_assignments' =>
                function ($q)
                {
                    $q->where(TrooperAssignment::IS_MEMBER, true);
                }
        ];

        return $query
            ->with($with)
            ->where(self::MEMBERSHIP_STATUS, MembershipStatus::PENDING)
            ->orderBy(self::NAME);
    }

    /**
     * Scope a query to troopers that can be moderated by a given trooper.
     *
     * @param Builder<Trooper> $query The Eloquent query builder.
     * @param Trooper $trooper The moderator trooper.
     * @return Builder<Trooper>
     */
    public function scopeModeratedBy(Builder $query, Trooper $trooper): Builder
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
                ->join('tt_trooper_assignments as ta_candidate', 'ta_candidate.trooper_id', '=', 'tt_troopers.id')
                ->join('tt_organizations as org_candidate', 'ta_candidate.organization_id', '=', 'org_candidate.id')
                ->where('ta_moderator.trooper_id', $trooper->id)
                ->where('ta_moderator.is_moderator', true)
                ->whereRaw('org_candidate.node_path LIKE CONCAT(org_moderator.node_path, "%")');
        });
    }

    /**
     * Scope a query to search for troopers by a given search term.
     *
     * @param Builder<Trooper> $query The Eloquent query builder.
     * @param string $search_term The term to search for in name, username, and email fields.
     * @return Builder<Trooper>
     */
    public function scopeSearchFor(Builder $query, string $search_term): Builder
    {
        if (!str_starts_with($search_term, '%'))
        {
            $search_term = '%' . $search_term;
        }

        if (!str_ends_with($search_term, '%'))
        {
            $search_term .= '%';
        }

        return $query->where(function ($query) use ($search_term)
        {
            $query->where(self::EMAIL, 'like', $search_term)
                ->orWhere(self::USERNAME, 'like', $search_term)
                ->orWhere(self::NAME, 'like', $search_term);
        });
    }
}