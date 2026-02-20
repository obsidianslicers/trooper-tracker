<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Enums\MembershipRole;
use App\Models\Trooper;

/**
 * Command to update a trooper's authority level within organizations.
 *
 * Updates TrooperAssignment records to set a trooper's membership_role
 * (MEMBER, MODERATOR, or ADMINISTRATOR) for specified organizations.
 * This controls what permissions the trooper has within each organization.
 *
 * @see UpdateTrooperAuthorityCommandHandler
 */
readonly class UpdateTrooperAuthorityCommand
{
    /**
     * Create a new command instance.
     *
     * @param  Trooper  $trooper  The trooper whose authority to update
     * @param  string|MembershipRole  $membership_role  The role to assign (MEMBER, MODERATOR, or ADMINISTRATOR)
     * @param  array<int, array{is_moderator: bool|null}>  $valid_data  Organization IDs mapped to assignment data
     */
    public function __construct(
        public Trooper $trooper,
        public string|MembershipRole $membership_role,
        public array $valid_data,
    ) {}
}
