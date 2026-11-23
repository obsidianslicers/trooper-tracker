<?php

declare(strict_types=1);

namespace App\Messages\Notices\PageData;

use App\Messages\Notices\Queries\GetFirstUnreadNotice;
use App\Messages\Notices\Queries\GetUnreadNoticeCount;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The trooper's unread notices for the notice widget: how many, and the notice itself when there
 * is only one.
 *
 * @method static array<string, mixed> call(Actor $actor)
 */
final class NoticeWidgetPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(private readonly Actor $actor) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $count = GetUnreadNoticeCount::call(trooper: $this->actor);

        return [
            'count' => $count,
            'notice' => $count === 1 ? GetFirstUnreadNotice::call(trooper: $this->actor) : null,
        ];
    }
}
