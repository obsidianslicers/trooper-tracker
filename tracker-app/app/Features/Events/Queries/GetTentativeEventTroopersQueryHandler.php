<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventTrooper;
use Illuminate\Database\Eloquent\Builder;

/**
 * @implements QueryHandlerInterface<GetTentativeEventTroopersQuery>
 */
readonly class GetTentativeEventTroopersQueryHandler implements QueryHandlerInterface
{
    public function __invoke(object $message): mixed
    {
        return EventTrooper::where(EventTrooper::STATUS, EventTrooperStatus::TENTATIVE)
            ->whereHas('event_shift.event', function (Builder $q) {
                $q->where(Event::STATUS, EventStatus::OPEN)
                    ->whereBetween(Event::EVENT_START, [now(), now()->addDays(7)]);
            })
            ->with(['trooper', 'event_shift.event'])
            ->get();
    }
}
