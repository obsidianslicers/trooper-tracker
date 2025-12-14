<?php

namespace App\Models;

use App\Enums\EventTrooperStatus;
use App\Models\Base\EventTrooper as BaseEventTrooper;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasEventTrooperScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventTrooper extends BaseEventTrooper
{
    use HasEventTrooperScopes;
    use HasFactory;
    use HasTrooperStamps;

    protected function casts(): array
    {
        return array_merge($this->casts, [
            self::STATUS => EventTrooperStatus::class,
        ]);
    }

    public function backup_costume(): BelongsTo
    {
        return $this->belongsTo(OrganizationCostume::class, self::BACKUP_COSTUME_ID);
    }

    public function added_by_trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class, self::ADDED_BY_TROOPER_ID);
    }

    public function getAttendedAttribute(): bool
    {
        return $this->status === EventTrooperStatus::ATTENDED;
    }

    public function getTimeDisplayAttribute(): string
    {
        //Sat - Oct 03, 2026 - 2:00pm - 4:00pm
        return $this->shift_starts_at->format('D') . ' - ' .
            $this->shift_starts_at->format('M d, Y') . ' - ' .
            $this->shift_starts_at->format('g:ia') . ' - ' .
            $this->shift_ends_at->format('g:ia');
    }

    public function canUpdateStatus(EventShift $event_shift): bool
    {
        if ($event_shift->is_open)
        {
            //  if they cancelled, and it's full they can't set to something else
            if ($this->status != EventTrooperStatus::GOING)
            {
                return !$event_shift->isFull();
            }

            return true;
        }

        return false;
    }
}
