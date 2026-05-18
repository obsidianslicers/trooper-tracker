<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
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
     * The pending TrooperOrganization must be for an organization within the moderator's tree.
     *
     * @param  Trooper  $trooper  The authenticated moderator
     * @param  TrooperOrganization  $trooper_organization  The pending record being acted on
     */
    public function moderate(Trooper $trooper, TrooperOrganization $trooper_organization): bool
    {
        if ($this->isAdministrator($trooper))
        {
            return true;
        }

        return Organization::moderatedBy($trooper)
            ->where(Organization::ID, $trooper_organization->organization_id)
            ->exists();
    }
}
