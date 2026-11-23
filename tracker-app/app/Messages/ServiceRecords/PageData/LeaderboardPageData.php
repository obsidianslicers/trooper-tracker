<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\PageData;

use App\Messages\Concerns\ResolvesLeaderboardFilters;
use App\Messages\Reports\Queries\GetLeaderboardMetrics;
use App\Models\Costume;
use App\Models\Organization;
use Hyperdrive\Message;

/**
 * The leaderboard: who trooped most, over a period and in a club, and the costumes to drill into.
 *
 * @method static array<string, mixed> call(?string $days = null, ?int $organization_id = null)
 */
final class LeaderboardPageData extends Message
{
    use ResolvesLeaderboardFilters;

    public const LIMIT = 30;

    public function __construct(
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

        return [
            'leaderboard' => GetLeaderboardMetrics::call(
                lookback: $days,
                organization: $organization,
                limit: self::LIMIT,
            ),
            'days' => $days,
            'organizations' => $organizations,
            'organization_id' => $organization?->id,
            'organization' => $organization,
            'costume_list' => Costume::whereNotIn(
                Costume::NAME,
                ['N/A', 'NA', Costume::HANDLER, Costume::COMMAND_STAFF]
            )
                ->orderBy(Costume::NAME)
                ->get([Costume::ID, Costume::NAME]),
        ];
    }
}
