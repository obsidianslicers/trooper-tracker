<?php

declare(strict_types=1);

namespace App\Messages\Notices\Queries;

use App\Models\Filters\NoticeFilter;
use App\Models\Notice;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * The notices a moderator may see on the Command Staff list, filtered by scope and organization.
 *
 * @method static LengthAwarePaginator call(NoticeFilter $filter, Trooper $moderator, int $page_size = 15)
 */
final class GetNoticesForModerator extends Message
{
    public function __construct(
        private readonly NoticeFilter $filter,
        private readonly Trooper $moderator,
        private readonly int $page_size = 15,
    ) {}

    public function handle(): LengthAwarePaginator
    {
        return Notice::with('organization')
            ->filterWith($this->filter)
            ->moderatedBy($this->moderator)
            ->paginate($this->page_size)
            ->withQueryString();
    }
}
