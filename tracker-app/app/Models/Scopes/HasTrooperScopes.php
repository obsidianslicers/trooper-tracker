<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\JoinRequestStatus;
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
     * Scope a query to find a trooper by their email.
     *
     * @param  Builder<Trooper>  $query  The Eloquent query builder.
     * @param  string  $email  The email to search for.
     * @return Builder<Trooper>
     */
    public function scopeByEmail(Builder $query, string $email): Builder
    {
        return $query->where(self::EMAIL, $email);
    }

    /**
     * Scope a query to find all troopers with a pending membership status.
     *
     * @param  Builder<Trooper>  $query  The Eloquent query builder.
     * @return Builder<Trooper>
     */
    public function scopePendingApprovals(Builder $query): Builder
    {
        $with = [
            'join_requests' => function ($q) {
                $q->pending()
                    ->with(['organization.parent', 'primaryOrganization']);
            },
            'trooper_assignments' => function ($q) {
                $q->where(TrooperAssignment::IS_MEMBER, true)
                    ->with('organization.parent');
            },
        ];

        return $query
            ->with($with)
            ->where(self::MEMBERSHIP_STATUS, MembershipStatus::PENDING)
            ->orderBy(self::DISPLAY_NAME);
    }

    /**
     * Scope a query to troopers that can be moderated by a given trooper.
     *
     * @param  Builder<Trooper>  $query  The Eloquent query builder.
     * @param  Trooper  $trooper  The moderator trooper.
     * @return Builder<Trooper>
     */
    public function scopeModeratedBy(Builder $query, Trooper $trooper): Builder
    {
        if ($trooper->is_administrator)
        {
            return $query;
        }

        return $query->whereExists(function ($sub) use ($trooper) {
            $sub->select(DB::raw(1))
                ->from('tt_trooper_assignments as ta_moderator')
                ->join('tt_organizations as org_moderator', 'ta_moderator.organization_id', '=', 'org_moderator.id')
                ->where('ta_moderator.trooper_id', $trooper->id)
                ->where('ta_moderator.is_moderator', true)
                ->where(function ($query): void {
                    $query->whereExists(function ($candidate_assignment): void {
                        $candidate_assignment->select(DB::raw(1))
                            ->from('tt_trooper_assignments as ta_candidate')
                            ->join('tt_organizations as org_candidate', 'ta_candidate.organization_id', '=', 'org_candidate.id')
                            ->whereColumn('ta_candidate.trooper_id', 'tt_troopers.id')
                            ->whereRaw('org_candidate.node_path LIKE CONCAT(org_moderator.node_path, "%")');
                    })->orWhereExists(function ($join_request): void {
                        $join_request->select(DB::raw(1))
                            ->from('tt_join_requests as jr_candidate')
                            ->join('tt_organizations as org_request', 'jr_candidate.organization_id', '=', 'org_request.id')
                            ->whereColumn('jr_candidate.trooper_id', 'tt_troopers.id')
                            ->where('jr_candidate.status', JoinRequestStatus::PENDING->value)
                            ->whereRaw('org_request.node_path LIKE CONCAT(org_moderator.node_path, "%")');
                    });
                });
        });
    }

    /**
     * Scope a query to search for troopers by a given search term.
     *
     * @param  Builder<Trooper>  $query  The Eloquent query builder.
     * @param  string  $search_term  The term to search for in display_name, legal_name, and email fields.
     * @return Builder<Trooper>
     */
    public function scopeSearchFor(Builder $query, string $search_term): Builder
    {
        if (!str_starts_with($search_term, '%'))
        {
            $search_term = '%'.$search_term;
        }

        if (!str_ends_with($search_term, '%'))
        {
            $search_term .= '%';
        }

        return $query->where(function ($query) use ($search_term) {
            $query->where(self::EMAIL, 'like', $search_term)
                ->orWhere(self::DISPLAY_NAME, 'like', $search_term)
                ->orWhere(self::LEGAL_NAME, 'like', $search_term);
        });
    }

    /**
     * Scope a query to only include troopers with active membership status.
     *
     * @param  Builder<Trooper>  $query  The Eloquent query builder.
     * @return Builder<Trooper>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(self::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE);
    }
}
