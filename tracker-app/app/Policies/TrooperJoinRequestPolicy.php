<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
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
     * @param  TrooperRequest  $trooper_request  The pending request being acted on
     */
    public function moderate(Trooper $trooper, TrooperRequest $trooper_request): bool
    {
        if ($this->isAdministrator($trooper))
        {
            return true;
        }

        return Organization::moderatedBy($trooper)
            ->where(Organization::ID, $trooper_request->organization_id)
            ->exists();
    }
}
