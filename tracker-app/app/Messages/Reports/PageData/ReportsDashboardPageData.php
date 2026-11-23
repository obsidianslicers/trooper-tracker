<?php

declare(strict_types=1);

namespace App\Messages\Reports\PageData;

use App\Enums\EventTrooperStatus;
use App\Messages\Reports\Queries\GetDashboardMetrics;
use App\Messages\Reports\Resources\DashboardImpactResource;
use App\Messages\Reports\Resources\DashboardLeaderboardRowResource;
use App\Messages\Reports\Resources\DashboardPersonnelResource;
use Hyperdrive\Message;

/**
 * Data for the Reports page: the operations dashboard for a lookback window, and the reports.
 *
 * @method static array<string, mixed> call(?int $days = null)
 */
final class ReportsDashboardPageData extends Message
{
    private const LOOKBACKS = [30, 60, 90, 180, 360];

    public function __construct(private readonly ?int $days = null) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $days = $this->days ?? 30;
        $metrics = GetDashboardMetrics::call(lookback: $days);

        return [
            'days' => $days,
            'lookbacks' => self::LOOKBACKS,
            'personnel' => new DashboardPersonnelResource($metrics['personnel']),
            'fulfillment_rate' => format_number($metrics['logistics']['fulfillment_rate'], 1),
            'impact' => new DashboardImpactResource($metrics['impact']),
            'awards_issued' => format_number($metrics['engagement']['award_velocity']),
            'photo_activity' => format_number($metrics['engagement']['photo_activity']),
            'leaderboard' => DashboardLeaderboardRowResource::collection(
                collect($metrics['leaderboard'])->values()
            ),
            'attended' => EventTrooperStatus::ATTENDED->label(),
        ];
    }
}
