<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Models\Base\Event as BaseEvent;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasEventScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends BaseEvent
{
    use HasFactory;
    use HasEventScopes;
    use HasTrooperStamps;

    /**
     * Get the troopers signed up for the event.
     */
    public function event_troopers(): HasMany
    {
        return $this->hasMany(EventTrooper::class);
    }

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

    public function timeDisplay(): string
    {
        //Sat - Oct 03, 2026 - 2:00pm - 4:00pm
        return $this->starts_at->format('D') . ' - ' .
            $this->starts_at->format('M d, Y') . ' - ' .
            $this->starts_at->format('g:ia') . ' - ' .
            $this->ends_at->format('g:ia');
    }
}
