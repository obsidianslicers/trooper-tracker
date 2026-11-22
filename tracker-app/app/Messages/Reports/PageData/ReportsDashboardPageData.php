<?php

declare(strict_types=1);

namespace App\Messages\Reports\PageData;

use App\Enums\EventTrooperStatus;
use App\Messages\Reports\Queries\GetDashboardMetrics;
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
            'personnel' => $this->numbers($metrics['personnel'], [
                'active_count', 'trooper_count', 'handler_count', 'new_enlistments',
                'ghost_count', 'attendance_count', 'attrition_risk',
            ]),
            'fulfillment_rate' => format_number($metrics['logistics']['fulfillment_rate'], 1),
            'impact' => [
                'total_credits' => format_number($metrics['impact']['total_credits'], 2),
                'volunteer_hours' => format_number($metrics['impact']['volunteer_hours']),
                'internal_donations' => format_number($metrics['impact']['internal_donations'], 2),
            ],
            'awards_issued' => format_number($metrics['engagement']['award_velocity']),
            'photo_activity' => format_number($metrics['engagement']['photo_activity']),
            'leaderboard' => $this->leaderboard($metrics['leaderboard']),
            'attended' => EventTrooperStatus::ATTENDED->label(),
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @param  array<int, string>  $keys
     * @return array<string, string>
     */
    private function numbers(array $metrics, array $keys): array
    {
        return collect($keys)
            ->mapWithKeys(fn (string $key): array => [$key => format_number($metrics[$key])])
            ->all();
    }

    /**
     * @param  iterable<int, array<string, mixed>>  $rows
     * @return array<int, array<string, string>>
     */
    private function leaderboard(iterable $rows): array
    {
        $dash = fn ($value, int $decimals = 0): string => (float) $value == 0.0
            ? '-'
            : format_number($value, $decimals);

        return collect($rows)
            ->map(fn (array $row): array => [
                'name' => $row['name'],
                'events_completed' => $dash($row['events_completed']),
                'troopers_attended' => $dash($row['troopers_attended']),
                'direct_funds_raised' => $dash($row['direct_funds_raised'], 2),
                'indirect_funds_raised' => $dash($row['indirect_funds_raised'], 2),
                'total_funds_raised' => $dash($row['total_funds_raised'], 2),
            ])
            ->values()
            ->all();
    }
}
