<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FaqSection;
use App\Models\Trooper;
use App\Policies\Concerns\HasTrooperPermissionsTrait;

/**
 * Class FaqSectionPolicy
 *
 * Defines authorization rules for FAQ section-related actions.
 */
class FaqSectionPolicy
{
    use HasTrooperPermissionsTrait;

    /**
     * Determine whether the user can create FAQ sections.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @return bool True if the user is an administrator or moderator, false otherwise.
     */
    public function create(Trooper $trooper): bool
    {
        return $this->isAdministrator($trooper);
    }

    /**
     * Determine whether the user can update a FAQ section.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  FaqSection  $subject  The FAQ section being updated.
     * @return bool True if the user can moderate the subject FAQ section, false otherwise.
     */
    public function update(Trooper $trooper, FaqSection $subject): bool
    {
        return $this->isAdministrator($trooper);
    }

    /**
     * Determine whether the user can delete a FAQ section.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  FaqSection  $subject  The FAQ section being deleted.
     * @return bool True if the user is an administrator, false otherwise.
     */
    public function delete(Trooper $trooper, FaqSection $subject): bool
    {
        return $this->isAdministrator($trooper);
    }

    /**
     * Determine whether the user can restore a FAQ section.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  FaqSection  $subject  The FAQ section being restored.
     * @return bool True if the user is an administrator, false otherwise.
     */
    public function restore(Trooper $trooper, FaqSection $subject): bool
    {
        return $this->isAdministrator($trooper);
    }

    /**
     * Determine whether the user can permanently delete a FAQ section.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  FaqSection  $subject  The FAQ section being force-deleted.
     * @return bool True if the user is an administrator, false otherwise.
     */
    public function forceDelete(Trooper $trooper, FaqSection $subject): bool
    {
        return $this->isAdministrator($trooper);
    }
}
