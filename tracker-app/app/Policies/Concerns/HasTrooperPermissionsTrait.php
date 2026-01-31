<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Enums\MembershipRole;
use App\Models\Trooper;

/**
 * Trait HasTrooperPermissionsTrait
 *
 * Provides common permission checking methods for policy classes.
 */
trait HasTrooperPermissionsTrait
{
    /**
     * Check if the trooper has administrator role.
     *
     * @param  Trooper  $trooper  The trooper to check.
     * @return bool True if the trooper is an administrator, false otherwise.
     */
    protected function isAdministrator(Trooper $trooper): bool
    {
        return $trooper->membership_role === MembershipRole::ADMINISTRATOR;
    }

    /**
     * Check if the trooper has moderator role.
     *
     * @param  Trooper  $trooper  The trooper to check.
     * @return bool True if the trooper is a moderator, false otherwise.
     */
    protected function isModerator(Trooper $trooper): bool
    {
        return $trooper->membership_role === MembershipRole::MODERATOR;
    }
}
