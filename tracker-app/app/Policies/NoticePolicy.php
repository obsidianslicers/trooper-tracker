<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Notice;
use App\Models\Trooper;
use App\Policies\Concerns\HasTrooperPermissionsTrait;

/**
 * Class NoticePolicy
 *
 * Defines authorization rules for notice-related actions.
 */
class NoticePolicy
{
    use HasTrooperPermissionsTrait;

    /**
     * Determine whether the user can create notices.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @return bool True if the user is an administrator or moderator, false otherwise.
     */
    public function create(Trooper $trooper): bool
    {
        return $this->isAdministrator($trooper) || $this->isModerator($trooper);
    }

    /**
     * Determine whether the user can update a notice.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Notice  $subject  The notice being updated.
     * @return bool True if the user can moderate the subject notice, false otherwise.
     */
    public function update(Trooper $trooper, Notice $subject): bool
    {
        return $this->canModerate($trooper, $subject);
    }

    /**
     * Determine whether the user can delete a notice.
     * Deleting notices is not permitted through this policy.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Notice  $subject  The notice being deleted.
     * @return bool Always false.
     */
    public function delete(Trooper $trooper, Notice $subject): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore a notice.
     * Restoring notices is not permitted through this policy.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Notice  $subject  The notice being restored.
     * @return bool Always false.
     */
    public function restore(Trooper $trooper, Notice $subject): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete a notice.
     * Force deleting notices is not permitted through this policy.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Notice  $subject  The notice being force-deleted.
     * @return bool Always false.
     */
    public function forceDelete(Trooper $trooper, Notice $subject): bool
    {
        return false;
    }

    /**
     * Check if a user can moderate a subject notice.
     * An admin can moderate any notice. A moderator can moderate notices within their assigned scope.
     *
     * @param  Trooper  $trooper  The user performing the action (moderator).
     * @param  Notice  $subject  The notice being moderated.
     * @return bool True if the user has moderation rights over the subject notice, false otherwise.
     */
    private function canModerate(Trooper $trooper, Notice $subject): bool
    {
        if ($this->isAdministrator($trooper))
        {
            return true;
        }

        return Notice::moderatedBy($trooper)
            ->where(Notice::ID, $subject->id)
            ->exists();
    }
}
