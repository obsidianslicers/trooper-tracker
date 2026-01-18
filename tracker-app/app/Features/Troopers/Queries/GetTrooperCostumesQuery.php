<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Models\Trooper;

/**
 * Query to retrieve all costumes owned by a trooper.
 *
 * Returns the trooper's costume collection with related organization_costume
 * and organization data for display purposes.
 *
 * @see GetTrooperCostumesQueryHandler
 */
readonly class GetTrooperCostumesQuery
{
    /**
     * Create a new query instance.
     *
     * @param Trooper $trooper The trooper whose costumes to retrieve
     */
    public function __construct(public readonly Trooper $trooper)
    {
    }
}