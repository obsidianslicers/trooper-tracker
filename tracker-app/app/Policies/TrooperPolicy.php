<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use App\Policies\Concerns\HasTrooperPermissionsTrait;

/**
 * Class TrooperPolicy
 *
 * Defines authorization rules for trooper-related actions.
 */
class TrooperPolicy
{
    use HasTrooperPermissionsTrait;

    /**
     * Determine whether the user can view a specific trooper.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Trooper  $subject  The trooper being viewed.
     * @return bool True if the user can moderate the subject, false otherwise.
     */
    public function view(Trooper $trooper, Trooper $subject): bool
    {
        return $this->canModerate($trooper, $subject);
    }

    /**
     * Determine whether the user can create troopers.
     * Always returns false as creation is handled by the registration process.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @return bool Always false.
     */
    public function create(Trooper $trooper): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update a trooper.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Trooper  $subject  The trooper being updated.
     * @return bool True if the user can moderate the subject, false otherwise.
     */
    public function update(Trooper $trooper, Trooper $subject): bool
    {
        return $this->canModerate($trooper, $subject);
    }

    /**
     * Determine whether the user can moderate a trooper.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Trooper  $subject  The trooper being moderated.
     * @return bool True if the user can moderate the subject, false otherwise.
     */
    public function moderate(Trooper $trooper, Trooper $subject): bool
    {
        return $this->canModerate($trooper, $subject);
    }

    /**
     * Determine whether the user can update a trooper's authority/role.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Trooper  $subject  The trooper whose authority is being updated.
     * @return bool True if the user is an administrator, false otherwise.
     */
    public function updateAuthority(Trooper $trooper, Trooper $subject): bool
    {
        return $this->isAdministrator($trooper);
    }

    /**
     * Determine whether a trooper may request deletion of their own account.
     *
     * Blocked if a deletion request is already pending, preventing duplicate requests.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Trooper  $subject  The trooper whose account is being deleted.
     */
    public function requestDeletion(Trooper $trooper, Trooper $subject): bool
    {
        if ($trooper->id !== $subject->id)
        {
            return false;
        }

        return $trooper->deletion_requested_at === null;
    }

    /**
     * Determine whether the user can delete a trooper.
     * Deleting troopers is not permitted through this policy.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Trooper  $subject  The trooper being deleted.
     * @return bool Always false.
     */
    public function delete(Trooper $trooper, Trooper $subject): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore a trooper.
     * Restoring troopers is not permitted through this policy.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Trooper  $subject  The trooper being restored.
     * @return bool Always false.
     */
    public function restore(Trooper $trooper, Trooper $subject): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete a trooper.
     * Force deleting troopers is not permitted through this policy.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Trooper  $subject  The trooper being force-deleted.
     * @return bool Always false.
     */
    public function forceDelete(Trooper $trooper, Trooper $subject): bool
    {
        return false;
    }

    /**
     * Determine whether the user can mark a trooper's account as created in error.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Trooper  $subject  The trooper being voided.
     * @return bool True if the user is an administrator and the account is not already voided.
     */
    public function void(Trooper $trooper, Trooper $subject): bool
    {
        return $this->isAdministrator($trooper)
            && $subject->membership_status !== MembershipStatus::VOID;
    }

    /**
     * Determine whether the user can restore a voided trooper account.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Trooper  $subject  The trooper being unvoided.
     * @return bool True if the user is an administrator and the account is currently voided.
     */
    public function unvoid(Trooper $trooper, Trooper $subject): bool
    {
        return $this->isAdministrator($trooper)
            && $subject->membership_status === MembershipStatus::VOID;
    }

    /**
     * Determine whether the user can approve a trooper.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Trooper  $subject  The trooper being approved.
     * @return bool True if the user can moderate the subject, false otherwise.
     */
    public function approve(Trooper $trooper, Trooper $subject): bool
    {
        return $this->canModerate($trooper, $subject);
    }

    /**
     * Check if a user can moderate a subject trooper.
     * An admin can moderate any trooper. A moderator can moderate troopers within their assigned scope.
     *
     * @param  Trooper  $trooper  The user performing the action (moderator).
     * @param  Trooper  $subject  The trooper being moderated.
     * @return bool True if the user has moderation rights over the subject, false otherwise.
     */
    private function canModerate(Trooper $trooper, Trooper $subject): bool
    {
        if ($this->isAdministrator($trooper))
        {
            return true;
        }

        return Trooper::moderatedBy($trooper)
            ->where(Trooper::ID, $subject->id)
            ->exists();
    }
}
