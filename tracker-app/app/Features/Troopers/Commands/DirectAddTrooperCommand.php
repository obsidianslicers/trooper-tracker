<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\Organization;
use App\Models\Trooper;

/**
 * Command to directly add a trooper to an organization's roster.
 *
 * Used by moderators to add troopers to their club without requiring
 * a join request. Creates a TrooperAssignment and, when provided,
 * persists the identifier to TrooperOrganization.
 *
 * @see DirectAddTrooperCommandHandler
 */
readonly class DirectAddTrooperCommand
{
    /**
     * @param  Trooper  $trooper  The trooper being added to the organization
     * @param  Organization  $organization  The leaf-node organization to assign them to
     * @param  string|null  $identifier  Optional club-specific identifier (e.g., TK number)
     */
    public function __construct(
        public Trooper $trooper,
        public Organization $organization,
        public ?string $identifier,
    ) {}
}
