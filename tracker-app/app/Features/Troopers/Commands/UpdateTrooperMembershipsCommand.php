<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\Trooper;

readonly class UpdateTrooperMembershipsCommand
{
    /**
     * Update the trooper's organization memberships.
     *
     * Iterates through the provided organizations array and creates or updates
     * TrooperAssignment records where an assignment organization ID is specified.
     * Sets the is_member flag to true for all processed assignments.
     *
     * @param Trooper $trooper The trooper whose memberships to update.
     * @param array $organizations Array of organization data with 'assignment' keys containing organization IDs.
     */
    public function __construct(
        public Trooper $trooper,
        public array $valid_data,
    ) {
    }
}

