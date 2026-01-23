<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Models\Filters\TrooperFilter;

/**
 * Query to retrieve troopers for use in picker/dropdown components.
 *
 * Supports filtering troopers by:
 * - Organization membership (specific organization)
 * - Search criteria (name, email, TK number via TrooperFilter)
 * - Role filters (admin, moderator, etc. via TrooperFilter)
 *
 * Results are ordered by trooper name for consistent UI display.
 *
 * @see GetTroopersForPickerQueryHandler
 */
readonly class GetTroopersForPickerQuery
{
    /**
     * Create a new query instance.
     *
     * @param TrooperFilter $filter Search and role filtering criteria
     * @param int|null $organization_id Optional organization ID to filter by membership
     */
    public function __construct(
        public readonly TrooperFilter $filter,
        public readonly ?int $organization_id = null
    ) {
    }
}