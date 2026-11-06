<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Database\Eloquent\Builder;

trait HasTrooperCostumeScopes
{
    /**
     * Scope to trooper costumes a moderator may approve or revoke (see ModeratorCoverage).
     * Nobody reviews their own costumes, administrators included.
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

        return $query->whereHas('organization_costume', fn (Builder $costume) => ModeratorCoverage::apply(
            $costume,
            $moderator,
            $query->qualifyColumn(TrooperCostume::TROOPER_ID),
            $costume->qualifyColumn(OrganizationCostume::ORGANIZATION_ID),
        ));
    }
}
