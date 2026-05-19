<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Illuminate\Support\Collection;

/**
 * @implements QueryHandlerInterface<GetExpiredVisitorsQuery>
 */
readonly class GetExpiredVisitorsQueryHandler implements QueryHandlerInterface
{
    public function __invoke(object $message): Collection
    {
        return Trooper::where(Trooper::MEMBERSHIP_ROLE, MembershipRole::VISITOR)
            ->where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE)
            ->where(Trooper::VISITOR_EXPIRES_AT, '<', now())
            ->whereNull(Trooper::VISITOR_NOTIFIED_AT)
            ->get();
    }
}
