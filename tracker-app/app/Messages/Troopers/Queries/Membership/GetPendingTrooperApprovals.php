<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Membership;

use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * @method static Collection call(Trooper $moderator)
 */
final class GetPendingTrooperApprovals extends Message
{
    public function __construct(
        private readonly Trooper $moderator,
    ) {
    }

    public function handle(): Collection
    {
        return Trooper::pendingApprovals()->moderatedBy($this->moderator)->get();
    }
}
