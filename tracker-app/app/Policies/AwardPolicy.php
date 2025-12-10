<?php

namespace App\Policies;

use App\Models\Award;
use App\Models\Trooper;

/**
 * Class AwardPolicy
 *
 * Defines authorization rules for organization-related actions.
 */
class AwardPolicy
{
    use HasTrooperPermissionsTrait;

    /**
     * Determine whether the user can create organizations.
     *
     * @param Trooper $trooper The authenticated user performing the action.
     * @return bool True if the user is an administrator, false otherwise.
     */
    public function create(Trooper $trooper): bool
    {
        return $this->isAdministrator($trooper) || $this->isModerator($trooper);
    }

    /**
     * Determine whether the user can update an organization.
     *
     * @param Trooper $trooper The authenticated user performing the action.
     * @param Award $subject The organization being updated.
     * @return bool True if the user can moderate the subject organization, false otherwise.
     */
    public function update(Trooper $trooper, Award $subject): bool
    {
        return $this->canModerate($trooper, $subject);
    }

    /**
     * Determine whether the user can delete an organization.
     * Deleting organizations is not permitted through this policy.
     *
     * @param Trooper $trooper The authenticated user performing the action.
     * @param Award $subject The organization being deleted.
     * @return bool Always false.
     */
    public function delete(Trooper $trooper, Award $subject): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore an organization.
     * Restoring organizations is not permitted through this policy.
     *
     * @param Trooper $trooper The authenticated user performing the action.
     * @param Award $subject The organization being restored.
     * @return bool Always false.
     */
    public function restore(Trooper $trooper, Award $subject): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete an organization.
     * Force deleting organizations is not permitted through this policy.
     *
     * @param Trooper $trooper The authenticated user performing the action.
     * @param Award $subject The organization being force-deleted.
     * @return bool Always false.
     */
    public function forceDelete(Trooper $trooper, Award $subject): bool
    {
        return false;
    }

    /**
     * Check if a user can moderate a subject organization.
     * An admin can moderate any organization. A moderator can moderate organizations within their assigned scope.
     *
     * @param Trooper $trooper The user performing the action (moderator).
     * @param Award $subject The organization being moderated.
     * @return bool True if the user has moderation rights over the subject organization, false otherwise.
     */
    private function canModerate(Trooper $trooper, Award $subject): bool
    {
        if ($this->isAdministrator($trooper))
        {
            return true;
        }

        return Award::moderatedBy($trooper)
            ->where(Award::ID, $subject->id)
            ->exists();
    }
}
