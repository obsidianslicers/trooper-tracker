<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Provides query scopes for filtering Costume models.
 *
 * This trait adds common query scopes to models that need to filter costumes
 * by trooper approval status and organization membership.
 *
 * Typically used on the Costume model to support queries like:
 * - Finding costumes a specific trooper has approved
 * - Filtering by organization membership
 * - Eager loading related organization details
 *
 * @see Costume
 */
trait HasCostumeScopes
{
    /**
     * Scope: Filter costumes approved for a specific trooper.
     *
     * Returns costumes that either:
     * 1. Have at least one organization in which the trooper has approved the costume, OR
     * 2. Are named "Command Staff" or "Handler" (always included regardless of trooper)
     *
     * The scope eager loads organization_costumes with organization details and filters
     * them by trooper approval status.
     *
     * Usage:
     * ```php
     * Costume::forTrooper($trooper_id)->get();
     * Costume::forTrooper($trooper_id, [1, 2, 3])->get();
     * ```
     *
     * @param Builder $query The query builder instance
     * @param int $trooper_id The ID of the trooper to filter by
     * @param Collection|array<int>|null $organization_ids Optional collection or array of organization IDs to filter by.
     *                                                        When provided, only includes organization_costumes
     *                                                        from these organizations.
     * @return Builder The modified query builder for method chaining
     */
    public function scopeForTrooper(Builder $query, int $trooper_id, Collection|array|null $organization_ids = null): Builder
    {
        return $query
            ->where(function ($query) use ($trooper_id)
            {
                $query->whereHas('organization_costumes.trooper_costumes', function ($query) use ($trooper_id)
                {
                    $query->where('trooper_id', $trooper_id);
                })
                    ->orWhereIn(Costume::NAME, [Costume::COMMAND_STAFF, Costume::HANDLER]);
            })
            ->with(['organization_costumes' => function ($query) use ($trooper_id, $organization_ids)
            {
                // Only pull the organization details if the trooper actually has the approval
                $with = 'organization:' . Organization::ID . ',' . Organization::NAME;
                $query->with($with)
                    ->whereHas('trooper_costumes', function ($q) use ($trooper_id)
                    {
                        $q->where('trooper_id', $trooper_id);
                    });

                if ($organization_ids !== null)
                {
                    $query->whereIn(OrganizationCostume::ORGANIZATION_ID, $organization_ids);
                }
            }]);
    }
}