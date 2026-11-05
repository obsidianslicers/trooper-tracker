<?php

declare(strict_types=1);

namespace App\Messages\Changes\Queries;

use App\Messages\Concerns\HasLookback;
use App\Models\EventTrooper;
use App\Models\ModelChange;
use App\Models\Trooper;
use Carbon\Carbon;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Query to retrieve model change history for a trooper.
 *
 * Returns ModelChange records for a trooper, including direct changes to the Trooper
 * model and changes to related EventTrooper records. Supports lookback filtering by
 * days (int), date string, or Carbon instance.
 *
 * @method static Collection<int, ModelChange> call(Trooper $trooper, int|string|Carbon $lookback)
 */
final class GetModelChangesForTrooper extends Message
{
    use HasLookback;

    /**
     * Create a new query instance.
     *
     * @param  Trooper  $trooper  The trooper whose change history to retrieve.
     * @param  int|string|Carbon  $lookback  Days to look back (int), date string, or Carbon date.
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly int|string|Carbon $lookback,
    ) {}

    /**
     * Execute the query to retrieve model change history.
     *
     * Converts the lookback parameter to a Carbon date if needed, then queries
     * ModelChange records for the trooper and their associated EventTrooper records.
     * Returns all changes since the lookback date.
     *
     * @return Collection<int, ModelChange> Collection of model changes.
     */
    public function handle(): Collection
    {
        $lookback = $this->parseLookback();

        $trooper_filter = function ($q)
        {
            // Direct changes to the Trooper model
            $q->where(ModelChange::AUDITABLE_TYPE, Trooper::class)
                ->where(ModelChange::AUDITABLE_ID, $this->trooper->id);
        };

        $event_trooper_filter = function ($q)
        {
            // Changes to EventTrooper rows that belong to this Trooper
            $q->where(ModelChange::AUDITABLE_TYPE, EventTrooper::class)
                ->whereIn(ModelChange::AUDITABLE_ID, EventTrooper::query()
                    ->where(EventTrooper::TROOPER_ID, $this->trooper->id)
                    ->select('id')
                );
        };

        return ModelChange::where($trooper_filter)
            ->orWhere($event_trooper_filter)
            ->recent($lookback)
            ->get();
    }
}
