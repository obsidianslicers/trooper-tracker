<?php

namespace App\Models;

use App\Enums\EventGuestStatus;
use App\Models\Concerns\HasTrooperStamps;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base\EventGuest as BaseEventGuest;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a guest's participation in an event shift.
 *
 * This model tracks guest signups for a specific shift, including who added
 * the guest, the guest status, and signup timing details.
 */
class EventGuest extends BaseEventGuest
{
    use HasFactory;
    use HasTrooperStamps;

    /**
     * Defines the attributes that should be cast.
     *
     * @return array<string, string>
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
}
