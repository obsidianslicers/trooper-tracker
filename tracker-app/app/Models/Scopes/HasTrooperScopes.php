<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\MembershipStatus;
use App\Enums\TrooperRequestStatus;
use App\Models\Trooper;
use App\Models\TrooperFriend;
use App\Models\TrooperAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Trait containing local scopes for the Trooper model.
 */
trait HasTrooperScopes
{
    /**
     * Scope a query to find friends of a trooper.
     *
     * @param  Builder<Trooper>  $query  The Eloquent query builder.
     * @param  Trooper  $trooper  The trooper whose friends to find.
     * @return Builder<Trooper>
     */
    public function scopeFriends(Builder $query, Trooper $trooper): Builder
    {
        $q = TrooperFriend::query()
            ->select(TrooperFriend::FRIEND_ID)
            ->where(TrooperFriend::TROOPER_ID, $trooper->id);

        return $query->whereIn(Trooper::ID, $q);
    }

    /**
     * Scope a query to find friends of a trooper using the message query naming.
     *
     * @param  Builder<Trooper>  $query  The Eloquent query builder.
     * @param  Trooper  $trooper  The trooper whose friends to find.
     * @return Builder<Trooper>
     */
    public function scopeFriendsOf(Builder $query, Trooper $trooper): Builder
    {
        return $this->scopeFriends($query, $trooper);
    }

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
            'trooper_requests' => function ($q)
            {
                $q->pending()
                    ->with(['organization.parent', 'primaryOrganization']);
            },
            'trooper_assignments' => function ($q)
            {
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

        return $query->whereExists(function ($sub) use ($trooper)
        {
            $sub->select(DB::raw(1))
                ->from('tt_trooper_assignments as ta_moderator')
                ->join('tt_organizations as org_moderator', 'ta_moderator.organization_id', '=', 'org_moderator.id')
                ->where('ta_moderator.trooper_id', $trooper->id)
                ->where('ta_moderator.is_moderator', true)
                ->where(function ($query): void
                {
                    $query->whereExists(function ($candidate_assignment): void
                    {
                        $candidate_assignment->select(DB::raw(1))
                            ->from('tt_trooper_assignments as ta_candidate')
                            ->join('tt_organizations as org_candidate', 'ta_candidate.organization_id', '=', 'org_candidate.id')
                            ->whereColumn('ta_candidate.trooper_id', 'tt_troopers.id')
                            ->whereRaw('org_candidate.node_path LIKE CONCAT(org_moderator.node_path, "%")');
                    })->orWhereExists(function ($trooper_request): void
                    {
                        $trooper_request->select(DB::raw(1))
                            ->from('tt_trooper_requests as tr_candidate')
                            ->join('tt_organizations as org_request', 'tr_candidate.organization_id', '=', 'org_request.id')
                            ->whereColumn('tr_candidate.trooper_id', 'tt_troopers.id')
                            ->where('tr_candidate.status', TrooperRequestStatus::PENDING->value)
                            ->whereRaw('org_request.node_path LIKE CONCAT(org_moderator.node_path, "%")');
                    });
                });
        });
    }

    /**
     * Scope a query to search for troopers by a given search term.
     *
     * The term is split on whitespace into tokens, and a trooper matches if every token
     * appears somewhere within the same field (in any order), so multi-word searches like
     * "drennan matthew" match a display_name of "Matthew Drennan" regardless of word order
     * or intervening text (e.g. a middle name). In addition to display_name, legal_name, and
     * email, this also matches a trooper's club/unit identifier (e.g. member ID).
     *
     * @param  Builder<Trooper>  $query  The Eloquent query builder.
     * @param  string  $search_term  The term to search for.
     * @return Builder<Trooper>
     */
    public function scopeSearchFor(Builder $query, string $search_term): Builder
    {
        $tokens = array_filter(preg_split('/\s+/', trim($search_term)));

        return $query->where(function ($query) use ($tokens)
        {
            foreach ([self::EMAIL, self::DISPLAY_NAME, self::LEGAL_NAME] as $field)
            {
                $query->orWhere(function ($query) use ($field, $tokens)
                {
                    foreach ($tokens as $token)
                    {
                        $query->where($field, 'like', '%' . $token . '%');
                    }
                });
            }

            $query->orWhereExists(function ($sub) use ($tokens)
            {
                $sub->select(DB::raw(1))
                    ->from('tt_trooper_organizations')
                    ->whereColumn('tt_trooper_organizations.trooper_id', 'tt_troopers.id')
                    ->whereNull('tt_trooper_organizations.deleted_at');

                foreach ($tokens as $token)
                {
                    $sub->where('tt_trooper_organizations.identifier', 'like', '%' . $token . '%');
                }
            });
        });
    }

    /**
     * Scope a query to search for troopers matching any token of a given search term.
     *
     * Unlike {@see scopeSearchFor()}, a trooper matches if just one token is found in any
     * field or identifier. Intended as a fallback for when a full, all-tokens-required
     * search comes back empty (e.g. a misremembered surname), so a search never dead-ends
     * with zero results.
     *
     * @param  Builder<Trooper>  $query  The Eloquent query builder.
     * @param  string  $search_term  The term to search for.
     * @return Builder<Trooper>
     */
    public function scopeSearchForAny(Builder $query, string $search_term): Builder
    {
        $tokens = array_filter(preg_split('/\s+/', trim($search_term)));

        return $query->where(function ($query) use ($tokens)
        {
            foreach ($tokens as $token)
            {
                foreach ([self::EMAIL, self::DISPLAY_NAME, self::LEGAL_NAME] as $field)
                {
                    $query->orWhere($field, 'like', '%' . $token . '%');
                }

                $query->orWhereExists(function ($sub) use ($token)
                {
                    $sub->select(DB::raw(1))
                        ->from('tt_trooper_organizations')
                        ->whereColumn('tt_trooper_organizations.trooper_id', 'tt_troopers.id')
                        ->whereNull('tt_trooper_organizations.deleted_at')
                        ->where('tt_trooper_organizations.identifier', 'like', '%' . $token . '%');
                });
            }
        });
    }

    /**
     * Scope a query to order troopers by how closely they match a search term.
     *
     * Ranks a display_name starting with the term first, then a display_name containing it,
     * then a legal_name or email containing it, and everything else (e.g. an identifier match,
     * or a match found only via individual tokens) last. Ties are broken by display_name.
     *
     * @param  Builder<Trooper>  $query  The Eloquent query builder.
     * @param  string  $search_term  The term the results are being ranked against.
     * @return Builder<Trooper>
     */
    public function scopeOrderByRelevance(Builder $query, string $search_term): Builder
    {
        $term = trim($search_term);
        $starts_with = $term . '%';
        $contains = '%' . $term . '%';

        return $query->selectRaw(
            'tt_troopers.*, CASE '
            . 'WHEN ' . self::DISPLAY_NAME . ' LIKE ? THEN 0 '
            . 'WHEN ' . self::DISPLAY_NAME . ' LIKE ? THEN 1 '
            . 'WHEN ' . self::LEGAL_NAME . ' LIKE ? THEN 2 '
            . 'WHEN ' . self::EMAIL . ' LIKE ? THEN 3 '
            . 'ELSE 4 END AS search_relevance',
            [$starts_with, $contains, $contains, $contains]
        )
            ->orderBy('search_relevance')
            ->orderBy(self::DISPLAY_NAME);
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
