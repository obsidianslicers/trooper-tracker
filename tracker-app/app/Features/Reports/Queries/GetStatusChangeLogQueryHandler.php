<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use App\Models\ModelChange;
use Illuminate\Support\Collection;

/**
 * Handler for retrieving event trooper status change log.
 *
 * Returns ModelChange audit records for genuine EventTrooper status
 * transitions to ATTENDED (not merely rows that are currently ATTENDED),
 * made by someone other than the trooper themselves, within the lookback
 * period, for troopers moderated by the specified moderator.
 *
 * @implements QueryHandlerInterface<GetStatusChangeLogQuery>
 */
readonly class GetStatusChangeLogQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve status change history.
     *
     * Retrieves ModelChange records where:
     * - The audited field is EventTrooper status
     * - The new value is ATTENDED
     * - The change was made by someone other than the trooper themselves
     * - Created within the lookback period
     * - For troopers moderated by the specified moderator
     *
     * @param  GetStatusChangeLogQuery  $message  The query containing moderator and lookback criteria.
     * @return Collection<int, ModelChange> Collection of status changes.
     */
    public function __invoke(object $message): mixed
    {
        $lookback = $message->parseLookback();

        $with = [
            'trooper',
            'auditable.trooper',
            'auditable.event_shift.event',
        ];

        return ModelChange::with($with)
            ->where(ModelChange::FIELD_NAME, EventTrooper::STATUS)
            ->where(ModelChange::NEW_VALUE, EventTrooperStatus::ATTENDED->value)
            ->whereHasMorph('auditable', [EventTrooper::class], function ($query) use ($message)
            {
                $query->whereHas('trooper', function ($qx) use ($message)
                {
                    $qx->moderatedBy($message->moderator);
                })
                    ->whereColumn(
                        'tt_event_troopers.'.EventTrooper::TROOPER_ID,
                        '!=',
                        'tt_model_changes.'.ModelChange::TROOPER_ID
                    );
            })
            ->recent($lookback)
            ->orderByDesc(ModelChange::CREATED_AT)
            ->get();
    }
}
