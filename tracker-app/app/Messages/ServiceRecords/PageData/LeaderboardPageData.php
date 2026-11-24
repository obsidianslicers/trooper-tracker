<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\PageData;

use App\Messages\Concerns\ResolvesLeaderboardFilters;
use App\Messages\Reports\Queries\GetCostumeTrooperLeaderboard;
use App\Messages\Reports\Queries\GetLeaderboardMetrics;
use App\Messages\ServiceRecords\Resources\CostumeOptionResource;
use App\Messages\ServiceRecords\Resources\CostumeStatsResource;
use App\Messages\ServiceRecords\Resources\DeployedCostumeResource;
use App\Messages\ServiceRecords\Resources\LeaderboardFiltersResource;
use App\Messages\ServiceRecords\Resources\LeaderboardTrooperResource;
use App\Messages\ServiceRecords\Resources\OrganizationActivityResource;
use App\Models\Costume;
use App\Models\Organization;
use Hyperdrive\Message;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

/**
 * The leaderboard (service-records/Leaderboard): who trooped most, over a period and in a club,
 * and the costumes to drill into. The leaderboard's sections are closures, so opening a costume's
 * panel (a partial reload of `costume`) doesn't count them again.
 *
 * @method static array<string, mixed> call(?string $days = null, ?int $organization_id = null, ?int $costume_id = null)
 */
final class LeaderboardPageData extends Message
{
    use ResolvesLeaderboardFilters;

    public const LIMIT = 30;

    /** @var array{dominance: Collection, diversity: Collection, operatives: Collection}|null */
    private ?array $metrics = null;

    public function __construct(
        private readonly ?string $days = null,
        private readonly ?int $organization_id = null,
        private readonly ?int $costume_id = null,
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
            'filters' => new LeaderboardFiltersResource(
                $organizations,
                $days,
                $this->leaderboardDayOptions(),
                $organization,
            ),
            'operatives' => fn (): AnonymousResourceCollection => LeaderboardTrooperResource::collection(
                $this->metrics($days, $organization)['operatives']
            ),
            'organization_activity' => fn (): AnonymousResourceCollection => OrganizationActivityResource::collection(
                $this->metrics($days, $organization)['dominance']
            ),
            'top_costumes' => fn (): AnonymousResourceCollection => DeployedCostumeResource::collection(
                $this->metrics($days, $organization)['diversity']
            ),
            'costumes' => fn (): AnonymousResourceCollection => CostumeOptionResource::collection(
                $this->costumeOptions()
            ),
            'costume' => fn (): ?CostumeStatsResource => $this->costumeStats($days, $organization),
        ];
    }

    /**
     * @return array{dominance: Collection, diversity: Collection, operatives: Collection}
     */
    private function metrics(?int $days, ?Organization $organization): array
    {
        return $this->metrics ??= GetLeaderboardMetrics::call(
            lookback: $days,
            organization: $organization,
            limit: self::LIMIT,
        );
    }

    /**
     * @return Collection<int, Costume>
     */
    private function costumeOptions(): Collection
    {
        return Costume::whereNotIn(Costume::NAME, ['N/A', 'NA', Costume::HANDLER, Costume::COMMAND_STAFF])
            ->orderBy(Costume::NAME)
            ->get([Costume::ID, Costume::NAME]);
    }

    private function costumeStats(?int $days, ?Organization $organization): ?CostumeStatsResource
    {
        $costume = $this->costume_id ? Costume::find($this->costume_id) : null;

        if ($costume === null)
        {
            return null;
        }

        $result = GetCostumeTrooperLeaderboard::call(
            costume: $costume,
            lookback: $days,
            organization: $organization,
            limit: CostumeLeaderboardPageData::LIMIT,
        );

        return new CostumeStatsResource($costume, $result['top_troopers'], $result['stats']);
    }
}
