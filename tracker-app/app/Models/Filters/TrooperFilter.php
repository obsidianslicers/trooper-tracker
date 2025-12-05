<?php

declare(strict_types=1);

namespace App\Models\Filters;

use App\Enums\MembershipRole;
use App\Models\Trooper;
use Illuminate\Database\Eloquent\Builder;

/**
 * Applies filters to the Trooper query based on HTTP request parameters.
 *
 * This class extends the base QueryFilter and defines specific methods for filtering troopers
 * by membership role and a search term.
 */
class TrooperFilter extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'membership_role' => 'membershipRole',
            'search_term' => 'searchTerm',
        ];
    }

    /**
     * Filters the query by membership role.
     *
     * @param Builder $query The Eloquent query builder.
     * @param string $value The role value from the request.
     * @return Builder The modified query builder.
     */
    protected function membershipRole(Builder $query, $value): Builder
    {
        $role = MembershipRole::from($value);

        return $query->where(Trooper::MEMBERSHIP_ROLE, $role);
    }

    /**
     * Applies a search scope to the query based on a search term.
     *
     * The search is only applied if the term is 3 or more characters long.
     *
     * @param Builder $query The Eloquent query builder.
     * @param string $value The search term from the request.
     * @return Builder The modified query builder.
     */
    protected function searchTerm(Builder $query, $value): Builder
    {
        if (strlen($value) >= 3)
        {
            return $query->searchFor($value);
        }

        return $query;
    }
}