<?php

namespace App\Models;

use App\Enums\EventGuestStatus;
use App\Models\Concerns\HasTrooperStamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base\EventGuest as BaseEventGuest;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a guest signup for an event shift.
 *
 * Adds child-model documentation on top of base generated metadata.
 *
 * @property EventGuestStatus $status
 * @property-read Trooper $added_by_trooper
 */
class EventGuest extends BaseEventGuest
{
    use HasFactory;
    use HasTrooperStamps;

    /**
     * Defines the model attribute casts.
     *
     * @return array<string, class-string<EventGuestStatus>|string>
     */
    protected function casts(): array
    {
        return array_merge($this->casts, [
            self::STATUS => EventGuestStatus::class,
        ]);
    }

    /**
     * Retrieves the trooper who added this event guest.
     *
     * @return BelongsTo
     */
    public function added_by_trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class, self::ADDED_BY_TROOPER_ID);
    }

    /**
     * Determines whether this guest status can be updated by the given trooper.
     *
     * @param Trooper $actor The trooper attempting the status update.
     * @return bool True when update rules permit changing guest status.
     */
    public function canUpdateStatus(Trooper $actor): bool
    {
        if (!($this->event_shift->is_open && $this->hasOwnership($actor)))
        {
            return false;
        }

        if ($this->status === EventGuestStatus::CANCELLED && $this->added_by_trooper_id !== null)
        {
            $guests_allowed = $this->event_shift->event->guests_allowed;

            if ($guests_allowed !== null)
            {
                $active_guests = $this->event_shift->event_guests()
                    ->where(self::ADDED_BY_TROOPER_ID, $this->added_by_trooper_id)
                    ->where(self::STATUS, '!=', EventGuestStatus::CANCELLED)
                    ->count();

                if ($active_guests >= $guests_allowed)
                {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Determines whether this guest name can be updated by the given trooper.
     *
     * @param Trooper $actor The trooper attempting the name update.
     * @return bool True when the shift is open and the actor owns the signup.
     */
    public function canUpdateName(Trooper $actor): bool
    {
        return $this->event_shift->is_open && $this->hasOwnership($actor);
    }

    /**
     * Determines whether the given trooper owns this guest signup.
     *
     * @param Trooper $actor The trooper to check for ownership.
     * @return bool True when the guest was added by the given trooper.
     */
    private function hasOwnership(Trooper $actor): bool
    {
        return $this->added_by_trooper_id == $actor->id;
    }
}
