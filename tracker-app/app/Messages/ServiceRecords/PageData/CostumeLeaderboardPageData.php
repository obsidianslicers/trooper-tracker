<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\PageData;

use App\Messages\Concerns\ResolvesLeaderboardFilters;
use App\Messages\Reports\Queries\GetCostumeTrooperLeaderboard;
use App\Models\Costume;
use App\Models\Organization;
use Hyperdrive\Message;

/**
 * One costume's leaderboard: who trooped in it most, over a period and in a club.
 *
 * @method static array<string, mixed> call(Costume $costume, ?string $days = null, ?int $organization_id = null)
 */
final class CostumeLeaderboardPageData extends Message
{
    use ResolvesLeaderboardFilters;

    public const LIMIT = 30;

    public function __construct(
        private readonly Costume $costume,
        private readonly ?string $days = null,
        private readonly ?int $organization_id = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $days = $this->leaderboardDays($this->days);
        $organizations = $this->leaderboardOrganizations();
        $organization = $this->organization_id
            ? $organizations->firstWhere(Organization::ID, $this->organization_id)
            : null;

        $result = GetCostumeTrooperLeaderboard::call(
            costume: $this->costume,
            lookback: $days,
            organization: $organization,
            limit: self::LIMIT,
        );

        return [
            'costume' => $this->costume,
            'top_troopers' => $result['top_troopers'],
            'stats' => $result['stats'],
            'days' => $days,
            'organizations' => $organizations,
            'organization_id' => $organization?->id,
            'organization' => $organization,
        ];
    }
}
