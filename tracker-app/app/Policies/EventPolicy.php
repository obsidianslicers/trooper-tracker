<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Policies\Concerns\HasTrooperPermissionsTrait;

/**
 * Class EventPolicy
 *
 * Defines authorization rules for event-related actions.
 */
class EventPolicy
{
    use HasTrooperPermissionsTrait;

    /**
     * Determine whether the user can create events.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @return bool True if the user is an administrator, moderator or event coordinator, false otherwise.
     */
    public function create(Trooper $trooper): bool
    {
        return $this->isAdministrator($trooper)
            || $this->isModerator($trooper)
            || $trooper->is_event_coordinator;
    }

    /**
     * Determine whether the user can copy an event into a new one.
     *
     * Moderators can copy what they can update. Event coordinators can copy any event
     * hosted by an organization they belong to, since the copy becomes their own event.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Event  $subject  The event being copied.
     * @return bool True if the user can copy the subject event, false otherwise.
     */
    public function copy(Trooper $trooper, Event $subject): bool
    {
        if ($trooper->is_event_coordinator)
        {
            return Organization::eventHostableBy($trooper)
                ->where(Organization::ID, $subject->organization_id)
                ->exists();
        }

        return $this->canModerate($trooper, $subject);
    }

    /**
     * Determine whether the user can update an event.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Event  $subject  The event being updated.
     * @return bool True if the user can moderate the subject event, false otherwise.
     */
    public function update(Trooper $trooper, Event $subject): bool
    {
        return $this->canModerate($trooper, $subject);
    }

    /**
     * Determine whether the user can delete an event.
     * Deleting events is not permitted through this policy.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Event  $subject  The event being deleted.
     * @return bool Always false.
     */
    public function delete(Trooper $trooper, Event $subject): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore an event.
     * Restoring events is not permitted through this policy.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Event  $subject  The event being restored.
     * @return bool Always false.
     */
    public function restore(Trooper $trooper, Event $subject): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete an event.
     * Force deleting events is not permitted through this policy.
     *
     * @param  Trooper  $trooper  The authenticated user performing the action.
     * @param  Event  $subject  The event being force-deleted.
     * @return bool Always false.
     */
    public function forceDelete(Trooper $trooper, Event $subject): bool
    {
        return false;
    }

    /**
     * Check if a user can moderate a subject event.
     * An admin can moderate any event. A moderator can moderate events within their assigned
     * scope. An event coordinator can moderate the events they created.
     *
     * @param  Trooper  $trooper  The user performing the action (moderator).
     * @param  Event  $subject  The event being moderated.
     * @return bool True if the user has moderation rights over the subject event, false otherwise.
     */
    private function canModerate(Trooper $trooper, Event $subject): bool
    {
        if ($this->isAdministrator($trooper))
        {
            return true;
        }

        return Event::managedBy($trooper)
            ->where(Event::ID, $subject->id)
            ->exists();
    }
}
