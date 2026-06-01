<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventTrooper;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @implements QueryHandlerInterface<GetCostumeArsenalQuery>
 */
readonly class GetCostumeArsenalQueryHandler implements QueryHandlerInterface
{
    public function __invoke(object $message): Collection
    {
        $lookback = $message->parseLookback();

        return $this->getCostumeArsenal($lookback);
    }

    private function getCostumeArsenal(?Carbon $date): Collection
    {
        return EventTrooper::where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
            ->whereHas('event_shift.event', function ($q) use ($date) {
                $q->where(Event::STATUS, EventStatus::CLOSED)
                    ->when($date, fn ($q) => $q->where(Event::EVENT_START, '>=', $date));
            })
            ->whereNotNull('costume_id')
            ->whereDoesntHave('costume', function ($q) {
                $q->whereIn(Costume::NAME, ['N/A', 'NA', Costume::COMMAND_STAFF, Costume::HANDLER]);
            })
            ->select(
                'costume_id',
                DB::raw('COUNT(*) as deployment_count'),
                DB::raw('COUNT(DISTINCT trooper_id) as unique_troopers'),
            )
            ->with(['costume' => fn ($q) => $q->select(Costume::ID, Costume::NAME)])
            ->groupBy('costume_id')
            ->orderByDesc('deployment_count')
            ->get();
    }
}
