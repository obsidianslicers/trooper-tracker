<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Award;
use App\Models\Trooper;
use App\Policies\Concerns\HasTrooperPermissionsTrait;

/**
 * Class AwardPolicy
 *
 * Defines authorization rules for award-related actions.
 */
class AwardPolicy
{
    use HasTrooperPermissionsTrait;

    /**
     * Determine whether the user can create awards.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @return bool True if the user is an administrator or moderator, false otherwise.
     */
    public function create(Trooper $trooper): bool
    {
        return $this->isAdministrator($trooper) || $this->isModerator($trooper);
    }

    /**
     * Determine whether the user can update an award.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Award  $subject  The award being updated.
     * @return bool True if the user can moderate the subject award, false otherwise.
     */
    public function update(Trooper $trooper, Award $subject): bool
    {
        return $this->canModerate($trooper, $subject);
    }

    /**
     * Determine whether the user can delete an award.
     * Deleting awards is not permitted through this policy.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Award  $subject  The award being deleted.
     * @return bool Always false.
     */
    public function delete(Trooper $trooper, Award $subject): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore an award.
     * Restoring awards is not permitted through this policy.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Award  $subject  The award being restored.
     * @return bool Always false.
     */
    public function restore(Trooper $trooper, Award $subject): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete an award.
     * Force deleting awards is not permitted through this policy.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Award  $subject  The award being force-deleted.
     * @return bool Always false.
     */
    public function forceDelete(Trooper $trooper, Award $subject): bool
    {
        return false;
    }

    /**
     * Check if a user can moderate a subject award.
     * An admin can moderate any award. A moderator can moderate awards within their assigned scope.
     *
     * @param  Trooper  $trooper  The user performing the action (moderator).
     * @param  Award  $subject  The award being moderated.
     * @return bool True if the user has moderation rights over the subject award, false otherwise.
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
