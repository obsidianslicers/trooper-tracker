<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
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

    public function getIsOpenAttribute(): bool
    {
        if (!$this->event->is_open)
        {
            return false;
        }
        return $this->status === EventStatus::OPEN;
    }

    public function getIsLockedAttribute(): bool
    {
        if ($this->event->is_locked)
        {
            return true;
        }
        return $this->status === EventStatus::SIGN_UP_LOCKED;
    }

    public function getTimeDisplayAttribute(): string
    {
        //Sat - Oct 03, 2026 - 2:00pm - 4:00pm
        return $this->shift_starts_at->format('D') . ' - ' .
            $this->shift_starts_at->format('M d, Y') . ' - ' .
            $this->shift_starts_at->format('g:ia') . ' - ' .
            $this->shift_ends_at->format('g:ia');
    }

    public function getShortTimeDisplayAttribute(): string
    {
        //10/03 - 2:00pm - 4:00pm
        return $this->shift_starts_at->format('m/d - g:i a') . ' - ' .
            $this->shift_ends_at->format('g:ia');
    }

    public function troopersMaxed(): bool
    {
        $troopers_allowed = $this->event->troopers_allowed;

        if ($troopers_allowed == null)
        {
            return false;
        }

        $troopers_signed_up = $this->event_troopers()->troopers()->count();

        return $troopers_signed_up >= $troopers_allowed;
    }

    public function handlersMaxed(): bool
    {
        $handlers_allowed = $this->event->handlers_allowed;

        if ($handlers_allowed == null)
        {
            return false;
        }

        $handlers_signed_up = $this->event_troopers()->handlers()->count();

        return $handlers_signed_up >= $handlers_allowed;
    }

    public function canSignUp(Trooper $trooper): bool
    {
        if ($this->is_open)
        {
            return $this->event_troopers->where(EventTrooper::TROOPER_ID, $trooper->id)->isEmpty();
        }

        return false;
    }
}
