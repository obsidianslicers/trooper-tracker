<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EventType;
use App\Models\Trooper;
use App\Policies\Concerns\HasTrooperPermissionsTrait;

/**
 * Event types change every trooper's troop totals, so only system owners
 * (and staff officers, who share administrator access) manage them.
 */
class EventTypePolicy
{
    use HasTrooperPermissionsTrait;

    public function viewAny(Trooper $trooper): bool
    {
        return $this->isAdministrator($trooper);
    }

    public function create(Trooper $trooper): bool
    {
        return $this->isAdministrator($trooper);
    }

    public function update(Trooper $trooper, EventType $subject): bool
    {
        return $this->isAdministrator($trooper);
    }

    /**
     * Types in use can only be hidden, so past events keep their type.
     */
    public function delete(Trooper $trooper, EventType $subject): bool
    {
        return $this->isAdministrator($trooper) && !$subject->events()->exists();
    }
}
