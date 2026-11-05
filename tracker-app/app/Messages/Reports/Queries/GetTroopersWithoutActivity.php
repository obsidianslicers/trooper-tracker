<?php

declare(strict_types=1);

namespace App\Messages\Reports\Queries;

use App\Enums\EventTrooperStatus;
use App\Enums\MembershipStatus;
use App\Messages\Concerns\HasLookback;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Carbon\Carbon;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Query to retrieve troopers without recent event activity.
 *
 * Returns active troopers who have not attended any events
 * within the specified lookback period.
 *
 * @method static Collection<int, Trooper> call(Trooper $moderator, int|string|Carbon $lookback)
 */
final class GetTroopersWithoutActivity extends Message
{
    use HasLookback;

    /**
     * Create a new query instance.
     *
     * @param  Trooper  $moderator  The moderator whose managed troopers to check.
     * @param  int|string|Carbon  $lookback  Days to look back (int), date string, or Carbon date.
     */
    public function __construct(
        private readonly Trooper $moderator,
        private readonly int|string|Carbon $lookback,
    ) {}

    /**
     * Execute the query to find inactive troopers.
     *
     * Retrieves active troopers managed by the moderator who:
     * - Have no ATTENDED event signups since the lookback date
     * - Are currently in ACTIVE membership status
     *
     * @return Collection<int, Trooper> Collection of inactive troopers.
     */
    public function handle(): Collection
    {
        $lookback = $this->parseLookback();

        $filter = function ($qx) use ($lookback)
        {
            $qx->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
                ->where(EventTrooper::SIGNED_UP_AT, '<', $lookback);
        };

        return Trooper::moderatedBy($this->moderator)
            ->where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE)
            ->whereDoesntHave('event_troopers', $filter)
            ->orderBy(Trooper::DISPLAY_NAME)
            ->get();
    }
}
