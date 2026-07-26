<?php

declare(strict_types=1);

namespace App\Models\Filters;

use App\Enums\MembershipRole;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Applies filters to the Trooper query based on HTTP request parameters.
 *
 * This class extends the base QueryFilter and defines specific methods for filtering troopers
 * by membership role and a search term.
 */
class TrooperFilter extends QueryFilter
{
    private bool $loose_search = false;

    /**
     * Clone this filter with loose (any-token) search matching enabled.
     *
     * Used as a fallback when an all-tokens-required search returns no results.
     *
     * @return static A cloned filter with loose search enabled.
     */
    public function useLooseSearch(): static
    {
        $clone = clone $this;
        $clone->loose_search = true;

        return $clone;
    }

    /**
     * Determine whether the request has a search term of two or more words.
     *
     * A loose-search fallback is only meaningful for multi-word terms, since a single
     * word matches identically whether all tokens or any token are required.
     *
     * @return bool True if the search term has two or more whitespace-separated words.
     */
    public function hasMultiWordSearchTerm(): bool
    {
        if (!$this->request->filled('search_term'))
        {
            return false;
        }

        $tokens = array_filter(preg_split('/\s+/', trim($this->request->input('search_term'))));

        return count($tokens) > 1;
    }

    /**
     * Get the search term if it's long enough to have actually been applied as a filter.
     *
     * @return string|null The trimmed search term, or null if absent or too short to search on.
     */
    public function searchTermValue(): ?string
    {
        if (!$this->request->filled('search_term'))
        {
            return null;
        }

        $value = trim($this->request->input('search_term'));

        return strlen($value) >= 3 ? $value : null;
    }

    protected function filters(): array
    {
        return [
            'membership_role' => 'membershipRole',
            'search_term' => 'searchTerm',
            'organization_id' => 'organization',
        ];
    }

    /**
     * Filters the query by membership role.
     *
     * @param  Builder  $query  The Eloquent query builder.
     * @param  string  $value  The role value from the request.
     * @return Builder The modified query builder.
     */
    protected function membershipRole(Builder $query, $value): Builder
    {
        $role = MembershipRole::from($value);

        return $query->where(Trooper::MEMBERSHIP_ROLE, $role);
    }

    /**
     * Filters the query to troopers assigned to the given organization or any of its descendants.
     *
     * @param  Builder  $query  The Eloquent query builder.
     * @param  string  $value  The organization ID from the request.
     * @return Builder The modified query builder.
     */
    protected function organization(Builder $query, $value): Builder
    {
        $organization = Organization::find((int) $value);

        if (!$organization)
        {
            return $query;
        }

        $node_path = $organization->node_path;

        return $query->whereExists(function ($sub) use ($node_path) {
            $sub->select(DB::raw(1))
                ->from('tt_trooper_assignments as ta_org')
                ->join('tt_organizations as org_match', 'ta_org.organization_id', '=', 'org_match.id')
                ->whereColumn('ta_org.trooper_id', 'tt_troopers.id')
                ->where('ta_org.is_member', true)
                ->whereRaw('org_match.node_path LIKE ?', [$node_path.'%']);
        });
    }

    /**
     * Applies a search scope to the query based on a search term.
     *
     * The search is only applied if the term is 3 or more characters long.
     *
     * @param  Builder  $query  The Eloquent query builder.
     * @param  string  $value  The search term from the request.
     * @return Builder The modified query builder.
     */
    protected function searchTerm(Builder $query, $value): Builder
    {
        if (strlen($value) >= 3)
        {
            return $this->loose_search ? $query->searchForAny($value) : $query->searchFor($value);
        }

        return $query;
    }
}
