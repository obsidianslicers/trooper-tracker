<?php

declare(strict_types=1);

namespace App\Messages\Reports\Queries;

use App\Enums\EventTrooperStatus;
use App\Messages\Concerns\HasLookback;
use App\Models\EventTrooper;
use App\Models\ModelChange;
use App\Models\Trooper;
use Carbon\Carbon;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Query to retrieve event trooper status change log.
 *
 * Returns ModelChange audit records for genuine EventTrooper status
 * transitions to ATTENDED within the lookback period, excluding self-updates.
 *
 * @method static Collection<int, ModelChange> call(Trooper $moderator, int|string|Carbon $lookback)
 */
final class GetStatusChangeLog extends Message
{
    use HasLookback;

    /**
     * Create a new query instance.
     *
     * @param  Trooper  $moderator  The moderator whose managed troopers to track.
     * @param  int|string|Carbon  $lookback  Days to look back (int), date string, or Carbon date.
     */
    public function __construct(
        private readonly Trooper $moderator,
        private readonly int|string|Carbon $lookback,
    ) {}

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
     * @return Collection<int, ModelChange> Collection of status changes.
     */
    public function handle(): Collection
    {
        $lookback = $this->parseLookback();

        $with = [
            'trooper',
            'auditable.trooper',
            'auditable.event_shift.event',
        ];

        return ModelChange::with($with)
            ->where(ModelChange::FIELD_NAME, EventTrooper::STATUS)
            ->where(ModelChange::NEW_VALUE, EventTrooperStatus::ATTENDED->value)
            ->whereHasMorph('auditable', [EventTrooper::class], function ($query)
            {
                $query->whereHas('trooper', function ($qx)
                {
                    $qx->moderatedBy($this->moderator);
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
