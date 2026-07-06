<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventTrooperStatus;
use App\Models\Base\EventShiftStation as BaseEventShiftStation;
use App\Models\Concerns\HasObserver;
use App\Models\Concerns\HasTrooperStamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventShiftStation extends BaseEventShiftStation
{
    use HasFactory;
    use HasObserver;
    use HasTrooperStamps;

    public function going_event_troopers(): HasMany
    {
        return $this->event_troopers()
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING);
    }

    public function goingCount(): int
    {
        if (array_key_exists('going_event_troopers_count', $this->attributes))
        {
            return (int) $this->attributes['going_event_troopers_count'];
        }

        return $this->going_event_troopers()->count();
    }

    public function hasRoom(?int $excluding_event_trooper_id = null): bool
    {
        $query = $this->going_event_troopers();

        if ($excluding_event_trooper_id !== null)
        {
            $query->where(EventTrooper::ID, '!=', $excluding_event_trooper_id);
        }

        return $query->count() < $this->troopers_allowed;
    }
}
