<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Trait containing local scopes for the OrganizationCostume model.
 */
trait HasOrganizationCostumeScopes
{
    /**
     * Scope a query to exclude a set of costumes by their IDs.
     *
     * @param Builder<self> $query The Eloquent query builder.
     * @param Collection|array<int> $costume_ids Costume IDs to exclude from results.
     * @return Builder<self>
     */
    public function scopeExcluding(Builder $query, Collection|array $costume_ids): Builder
    {
        return $query->whereNotIn(self::ID, $costume_ids);
    }
}