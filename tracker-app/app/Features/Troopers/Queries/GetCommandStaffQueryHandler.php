<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\MembershipRole;
use App\Models\Trooper;
use Illuminate\Support\Collection;

/**
 * Retrieves active command staff troopers.
 *
 * @implements QueryHandlerInterface<GetCommandStaffQuery>
 */
readonly class GetCommandStaffQueryHandler implements QueryHandlerInterface
{
    /**
     * Returns active administrators and moderators ordered by display name.
     *
     * @return Collection<int, Trooper>
     */
    public function __invoke(object $message): mixed
    {
        $roles = [MembershipRole::ADMINISTRATOR, MembershipRole::MODERATOR];

        return Trooper::active()
            ->whereIn(Trooper::MEMBERSHIP_ROLE, $roles)
            ->orderBy(Trooper::DISPLAY_NAME)
            ->get();
    }
}
