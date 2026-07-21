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
     * Check if the trooper has administrator role and is active.
     *
     * @param  Trooper  $trooper  The trooper to check.
     * @return bool True if the trooper is an active administrator, false otherwise.
     */
    protected function isAdministrator(Trooper $trooper): bool
    {
        return $trooper->is_administrator;
    }

    /**
     * Check if the trooper has moderator role and is active.
     *
     * @param  Trooper  $trooper  The trooper to check.
     * @return bool True if the trooper is an active moderator, false otherwise.
     */
    protected function isModerator(Trooper $trooper): bool
    {
        return $trooper->is_moderator;
    }
}
