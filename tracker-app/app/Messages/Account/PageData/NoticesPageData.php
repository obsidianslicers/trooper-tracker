<?php

declare(strict_types=1);

namespace App\Messages\Account\PageData;

use App\Messages\Notices\Queries\GetUnreadNotices;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The notices the signed-in trooper has not read yet.
 *
 * @method static array call(Actor $actor)
 */
final class NoticesPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(private readonly Actor $actor) {}

    public function handle(): array
    {
        return [
            'notices' => GetUnreadNotices::call(trooper: $this->actor),
        ];
    }
}
