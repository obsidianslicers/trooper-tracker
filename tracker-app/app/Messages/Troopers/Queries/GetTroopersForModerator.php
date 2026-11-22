<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries;

use App\Enums\MembershipStatus;
use App\Models\Filters\TrooperFilter;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * One page of the troopers a moderator looks after, filtered and sorted. Troopers created in
 * error are only listed for administrators. A search of several words that finds nobody is
 * tried again matching any of the words.
 *
 * @method static LengthAwarePaginator call(Trooper $moderator, TrooperFilter $filter, string $sort_col, string $sort_dir, int $page_size = 15)
 */
final class GetTroopersForModerator extends Message
{
    public function __construct(
        private readonly Trooper $moderator,
        private readonly TrooperFilter $filter,
        private readonly string $sort_col = Trooper::DISPLAY_NAME,
        private readonly string $sort_dir = 'asc',
        private readonly int $page_size = 15,
    ) {}

    public function handle(): LengthAwarePaginator
    {
        $troopers = $this->page($this->filter);

        if ($troopers->isEmpty() && $this->filter->hasMultiWordSearchTerm())
        {
            $troopers = $this->page($this->filter->useLooseSearch());
        }

        return $troopers;
    }

    private function page(TrooperFilter $filter): LengthAwarePaginator
    {
        return Trooper::moderatedBy($this->moderator)
            ->when(
                !$this->moderator->is_administrator,
                fn ($query) => $query->where(Trooper::MEMBERSHIP_STATUS, '!=', MembershipStatus::INVALID->value)
            )
            ->orderBy($this->sort_col, $this->sort_dir)
            ->filterWith($filter)
            ->paginate($this->page_size)
            ->withQueryString();
    }
}
