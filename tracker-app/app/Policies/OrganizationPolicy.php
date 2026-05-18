<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organization;
use App\Models\Trooper;
use App\Policies\Concerns\HasTrooperPermissionsTrait;

/**
 * Class OrganizationPolicy
 *
 * Defines authorization rules for organization-related actions.
 */
class OrganizationPolicy
{
    use HasTrooperPermissionsTrait;

    /**
     * Determine whether the user can create organizations.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @return bool True if the user is an administrator, false otherwise.
     */
    public function create(Trooper $trooper): bool
    {
        return $trooper->is_administrator;
    }

    /**
     * Determine whether the moderator can directly add a trooper to an organization.
     *
     * @param  Trooper       $trooper  The authenticated moderator
     * @param  Organization  $subject  The organization to recruit into
     * @return bool True if the user moderates the organization
     */
    public function recruit(Trooper $trooper, Organization $subject): bool
    {
        return $this->canModerate($trooper, $subject);
    }

    /**
     * Determine whether the user can update an organization.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Organization  $subject  The organization being updated.
     * @return bool True if the user can moderate the subject organization, false otherwise.
     */
    public function update(Trooper $trooper, Organization $subject): bool
    {
        return $this->canModerate($trooper, $subject);
    }

    /**
     * Alias for 'update'
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Organization  $subject  The organization being updated.
     * @return bool True if the user can moderate the subject organization, false otherwise.
     */
    public function moderate(Trooper $trooper, Organization $subject): bool
    {
        return $this->canModerate($trooper, $subject);
    }

    /**
     * Determine whether the user can delete an organization.
     * Deleting organizations is not permitted through this policy.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Organization  $subject  The organization being deleted.
     * @return bool Always false.
     */
    public function delete(Trooper $trooper, Organization $subject): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore an organization.
     * Restoring organizations is not permitted through this policy.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Organization  $subject  The organization being restored.
     * @return bool Always false.
     */
    public function restore(Trooper $trooper, Organization $subject): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete an organization.
     * Force deleting organizations is not permitted through this policy.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Organization  $subject  The organization being force-deleted.
     * @return bool Always false.
     */
    public function forceDelete(Trooper $trooper, Organization $subject): bool
    {
        return false;
    }

    /**
     * Check if a user can moderate a subject organization.
     * An admin can moderate any organization. A moderator can moderate organizations within their assigned scope.
     *
     * @param  Trooper  $trooper  The user performing the action (moderator).
     * @param  Organization  $subject  The organization being moderated.
     * @return bool True if the user has moderation rights over the subject organization, false otherwise.
     */
    private function canModerate(Trooper $trooper, Organization $subject): bool
    {
        if ($this->isAdministrator($trooper))
        {
            return true;
        }

        return Organization::moderatedBy($trooper)
            ->where(Organization::ID, $subject->id)
            ->exists();
    }
}
