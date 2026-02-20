<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\Trooper;

/**
 * Command to update a trooper's organization memberships.
 *
 * Creates or updates TrooperAssignment records to set which organizations,
 * regions, or units the trooper is a member of. Sets the is_member flag
 * to true for all specified assignments.
 *
 * @see UpdateTrooperMembershipsCommandHandler
 */
readonly class UpdateTrooperMembershipsCommand
{
    /**
     * Create a new command instance.
     *
     * @param  Trooper  $trooper  The trooper whose memberships to update
     * @param  array<int, array{assignment: int|null}>  $valid_data  Organization IDs mapped to assignment data
     */
    public function __construct(
        public Trooper $trooper,
        public array $valid_data,
    ) {}
}
