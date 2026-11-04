<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * @method static Collection call()
 */
final class GetExpiredVisitors extends Message
{
    public function handle(): Collection
    {
        return Trooper::where(Trooper::MEMBERSHIP_ROLE, MembershipRole::VISITOR)
            ->where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE)
            ->where(Trooper::VISITOR_EXPIRES_AT, '<', now())
            ->whereNull(Trooper::VISITOR_NOTIFIED_AT)
            ->get();
    }
}
