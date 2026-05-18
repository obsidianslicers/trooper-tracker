<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperJoinRequest;
use App\Policies\Concerns\HasTrooperPermissionsTrait;

/**
 * Authorization rules for club join request actions.
 */
class TrooperJoinRequestPolicy
{
    use HasTrooperPermissionsTrait;

    /**
     * Determine whether the moderator can approve or deny a join request.
     *
     * The join request must be for an organization within the moderator's tree.
     *
     * @param  Trooper             $trooper       The authenticated moderator
     * @param  TrooperJoinRequest  $join_request  The join request being acted on
     * @return bool
     */
    public function moderate(Trooper $trooper, TrooperJoinRequest $join_request): bool
    {
        if ($this->isAdministrator($trooper))
        {
            return true;
        }

        return Organization::moderatedBy($trooper)
            ->where(Organization::ID, $join_request->organization_id)
            ->exists();
    }
}
