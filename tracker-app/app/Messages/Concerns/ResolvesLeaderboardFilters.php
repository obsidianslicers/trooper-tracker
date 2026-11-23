<?php

declare(strict_types=1);

namespace App\Messages\Concerns;

use App\Models\Organization;
use Illuminate\Support\Collection;

/**
 * The period and club a leaderboard is filtered by.
 */
trait ResolvesLeaderboardFilters
{
    /**
     * The days a leaderboard looks back; anything else, or "all", means all time.
     *
     * @return array<int, int>
     */
    protected function leaderboardDayOptions(): array
    {
        return [30, 60, 90, 180, 360];
    }

    protected function leaderboardDays(?string $days): ?int
    {
        if ($days === null || $days === 'all')
        {
            return null;
        }

        return in_array((int) $days, $this->leaderboardDayOptions(), true) ? (int) $days : null;
    }

    /**
     * The top-level clubs a leaderboard can be narrowed to.
     *
     * @return Collection<int, Organization>
     */
    protected function leaderboardOrganizations(): Collection
    {
        return Organization::ofTypeOrganizations()
            ->whereNull(Organization::PARENT_ID)
            ->orderBy(Organization::NAME)
            ->get([
                Organization::ID,
                Organization::NAME,
                Organization::IMAGE_PATH_SM,
                Organization::IMAGE_PATH_LG,
                Organization::NODE_PATH,
            ]);
    }
}
