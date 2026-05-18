<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\Organization;
use App\Models\Trooper;

/**
 * Command to remove a trooper's membership from a top-level organization.
 *
 * Deletes the trooper-organization record and clears member assignments
 * within that organization's hierarchy.
 *
 * @see RemoveTrooperMembershipCommandHandler
 */
readonly class RemoveTrooperMembershipCommand
{
    public function __construct(
        public Trooper $trooper,
        public Organization $organization,
    ) {}
}
