<?php

declare(strict_types=1);

namespace App\Messages\Events\Queries;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventTrooper;
use Hyperdrive\Message;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @method static Collection<int, EventTrooper> call()
 */
final class GetTentativeEventTroopers extends Message
{
    public function handle(): Collection
    {
        return EventTrooper::where(EventTrooper::STATUS, EventTrooperStatus::TENTATIVE)
            ->whereHas('event_shift.event', function (Builder $q)
            {
                $q->where(Event::STATUS, EventStatus::OPEN)
                    ->whereBetween(Event::EVENT_START, [now(), now()->addDays(7)]);
            })
            ->with(['trooper', 'event_shift.event'])
            ->get();
    }
}
