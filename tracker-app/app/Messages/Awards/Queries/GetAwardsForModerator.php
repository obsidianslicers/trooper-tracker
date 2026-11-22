<?php

declare(strict_types=1);

namespace App\Messages\Awards\Queries;

use App\Models\Award;
use App\Models\Filters\AwardFilter;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * The awards a moderator may see on the Command Staff list, with how many troopers hold each.
 *
 * @method static LengthAwarePaginator call(AwardFilter $filter, Trooper $moderator, int $page_size = 15)
 */
final class GetAwardsForModerator extends Message
{
    public function __construct(
        private readonly AwardFilter $filter,
        private readonly Trooper $moderator,
        private readonly int $page_size = 15,
    ) {}

    public function handle(): LengthAwarePaginator
    {
        return Award::with('organization')
            ->withCount('troopers')
            ->filterWith($this->filter)
            ->moderatedBy($this->moderator)
            ->paginate($this->page_size)
            ->withQueryString();
    }
}
