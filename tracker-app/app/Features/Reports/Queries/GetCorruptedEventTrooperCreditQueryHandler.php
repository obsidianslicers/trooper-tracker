<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use Illuminate\Support\Collection;

/**
 * Handler for finding EventTrooper rows with wiped credit data.
 *
 * @implements QueryHandlerInterface<GetCorruptedEventTrooperCreditQuery>
 */
readonly class GetCorruptedEventTrooperCreditQueryHandler implements QueryHandlerInterface
{
    /**
     * @param  GetCorruptedEventTrooperCreditQuery  $message
     * @return Collection<int, EventTrooper>
     */
    public function __invoke(object $message): mixed
    {
        return EventTrooper::with(['trooper', 'event_shift.event'])
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
            ->when($message->trooper_id !== null, fn ($q) => $q->where(EventTrooper::TROOPER_ID, $message->trooper_id))
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNull(EventTrooper::COSTUME_ID)
                        ->where(EventTrooper::IS_HANDLER, false);
                })->orWhere(function ($q2) {
                    $q2->where(function ($q3) {
                        $q3->whereNull(EventTrooper::COSTUME_ORGANIZATION_IDS)
                            ->orWhereJsonLength(EventTrooper::COSTUME_ORGANIZATION_IDS, 0);
                    })->whereNull(EventTrooper::ORGANIZATION_ID);
                });
            })
            ->orderByDesc(EventTrooper::UPDATED_AT)
            ->get();
    }
}
