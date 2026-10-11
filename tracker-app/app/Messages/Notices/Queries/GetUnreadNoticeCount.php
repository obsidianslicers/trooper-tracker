<?php

declare(strict_types=1);

namespace App\Messages\Notices\Queries;

use App\Models\Notice;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * @method static Collection call(Trooper $trooper)
 */
final class GetUnreadNoticeCount extends Message
{
    public function __construct(
        private readonly Trooper $trooper
    ) {}

    public function handle(): int
    {
        return Notice::visibleTo($this->trooper, true)->count();
    }
}
