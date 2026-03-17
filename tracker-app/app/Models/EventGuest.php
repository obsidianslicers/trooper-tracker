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
     */
    public function added_by_trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class, self::ADDED_BY_TROOPER_ID);
    }

    /**
     * Determines whether this guest status can be updated by the given trooper.
     */
    public function canUpdateStatus(EventShift $event_shift, Trooper $trooper): bool
    {
        return $event_shift->is_open && $this->hasOwnership($trooper);
    }

    /**
     * Determines whether this guest name can be updated by the given trooper.
     */
    public function canUpdateName(EventShift $event_shift, Trooper $trooper): bool
    {
        return $event_shift->is_open && $this->hasOwnership($trooper);
    }

    /**
     * Determines whether the given trooper owns this guest signup.
     */
    private function hasOwnership(Trooper $trooper): bool
    {
        return $this->added_by_trooper_id == $trooper->id;
    }
}
