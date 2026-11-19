<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EventUpload;
use App\Models\Trooper;

class EventUploadPolicy
{
    /**
     * Uploaders can fix their own photos; officers who run the event can fix any of them.
     */
    public function changeType(Trooper $trooper, EventUpload $subject): bool
    {
        if ($subject->trooper_id === $trooper->id)
        {
            return true;
        }

        return $subject->event !== null && $trooper->can('update', $subject->event);
    }
}
