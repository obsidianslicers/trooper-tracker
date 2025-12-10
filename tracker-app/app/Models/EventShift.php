<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Models\Base\EventShift as BaseEventShift;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasEventShiftScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventShift extends BaseEventShift
{
    use HasEventShiftScopes;
    use HasFactory;
    use HasTrooperStamps;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts()
    {
        return array_merge($this->casts, [
            self::STATUS => EventStatus::class,
        ]);
    }

    /**
     * Get the display string for the event time.
     *
     * @return string
     */
    public function timeDisplay(): string
    {
        //Sat - Oct 03, 2026 - 2:00pm - 4:00pm
        return $this->shift_starts_at->format('D') . ' - ' .
            $this->shift_starts_at->format('M d, Y') . ' - ' .
            $this->shift_starts_at->format('g:ia') . ' - ' .
            $this->shift_ends_at->format('g:ia');
    }
}
