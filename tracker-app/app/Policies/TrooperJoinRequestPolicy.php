<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\JoinRequest;
use App\Models\Organization;
use App\Models\Trooper;
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
     * @param  Trooper  $trooper  The authenticated moderator
     * @param  JoinRequest  $join_request  The pending request being acted on
     */
    public function moderate(Trooper $trooper, JoinRequest $join_request): bool
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
