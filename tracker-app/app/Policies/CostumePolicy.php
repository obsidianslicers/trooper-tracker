<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Costume;
use App\Models\Trooper;
use App\Policies\Concerns\HasTrooperPermissionsTrait;

/**
 * Class CostumePolicy
 *
 * Defines authorization rules for costume-related actions.
 */
class CostumePolicy
{
    use HasTrooperPermissionsTrait;

    /**
     * Determine whether the user can create costumes.
     *
     * @param Trooper $trooper The authenticated user performing the action.
     * @return bool True if the user is an administrator, false otherwise.
     */
    public function create(Trooper $trooper): bool
    {
        return $trooper->is_administrator;
    }

    /**
     * Determine whether the user can update a costume.
     *
     * @param Trooper $trooper The authenticated user performing the action.
     * @param Costume $subject The costume being updated.
     * @return bool True if the user is an administrator, false otherwise.
     */
    public function update(Trooper $trooper, Costume $subject): bool
    {
        return $trooper->is_administrator;
    }

    /**
     * Determine whether the user can delete a costume.
     *
     * Deleting costumes is not permitted through this policy.
     *
     * @param Trooper $trooper The authenticated user performing the action.
     * @param Costume $subject The costume being deleted.
     * @return bool Always false.
     */
    public function delete(Trooper $trooper, Costume $subject): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore a costume.
     *
     * Restoring costumes is not permitted through this policy.
     *
     * @param Trooper $trooper The authenticated user performing the action.
     * @param Costume $subject The costume being restored.
     * @return bool Always false.
     */
    public function restore(Trooper $trooper, Costume $subject): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete a costume.
     *
     * Force deleting costumes is not permitted through this policy.
     *
     * @param Trooper $trooper The authenticated user performing the action.
     * @param Costume $subject The costume being force-deleted.
     * @return bool Always false.
     */
    public function forceDelete(Trooper $trooper, Costume $subject): bool
    {
        return false;
    }
}
