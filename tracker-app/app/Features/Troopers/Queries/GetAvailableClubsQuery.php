<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Models\Trooper;

/**
 * Query to retrieve leaf-node organizations a trooper can request to join.
 *
 * Excludes organizations the trooper is already a member of and those
 * they already have a pending join request for.
 *
 * @see GetAvailableClubsQueryHandler
 */
readonly class GetAvailableClubsQuery
{
    /**
     * @param  Trooper  $trooper  The trooper looking for available clubs
     */
    public function __construct(public readonly Trooper $trooper) {}
}
