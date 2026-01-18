<?php

declare(strict_types=1);

namespace App\Features\Notices\Queries;

use App\Models\Trooper;

/**
 * Query to retrieve notices for display to a trooper.
 *
 * This query supports retrieving all notices visible to the trooper.
 *
 * The results are always ordered by the notice's created_at field.
 */
readonly class GetTrooperNoticesQuery
{
    /**
     * Create a new query instance.
     *
     * @param Trooper $trooper The trooper requesting organizations
     */
    public function __construct(public readonly Trooper $trooper)
    {
    }
}