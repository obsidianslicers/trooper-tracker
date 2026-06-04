<?php

declare(strict_types=1);

namespace App\Models\Observers;

use App\Enums\EventStatus;
use App\Models\EventShift;
use RuntimeException;

class EventShiftObserver
{
    /**
     * Prevent closing a shift whose end time is still in the future.
     *
     * This guard fires on every create/update so no code path — commands,
     * admin forms, or direct Eloquent calls — can mark an unfinished shift
     * as CLOSED.
     */
    public function saving(EventShift $shift): void
    {
        if ($shift->status === EventStatus::CLOSED && $shift->shift_ends_at?->isFuture())
        {
            throw new RuntimeException(
                "Cannot close EventShift [{$shift->id}]: shift ends at {$shift->shift_ends_at} which is in the future.",
            );
        }
    }
}
