<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Resources\Record;

use App\Enums\AchievementType;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * The service record's left column (service-records/components/record/ServiceSummary): the
 * totals, troops per club, funds, donations and milestones. Numbers come formatted.
 *
 * @property Trooper $resource
 */
class ServiceSummaryResource extends JsonResource
{
    /**
     * @param  array<string, mixed>  $summary  GetTrooperServiceRecord's service_summary
     * @param  Collection<int, Organization&object{troop_count: int}>  $club_troop_counts
     */
    public function __construct(
        Trooper $trooper,
        private readonly array $summary,
        private readonly Collection $club_troop_counts,
        private readonly bool $is_active_donor,
    ) {
        parent::__construct($trooper);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $summary = $this->summary;

        return [
            'total_shifts' => $this->number($summary['total_shifts']),
            'total_hours' => $this->number($summary['total_hours']),
            'theme' => $this->resource->theme->label(),
            'clubs' => $this->club_troop_counts
                ->map(fn (Organization $club): array => [
                    Organization::NAME => $club->name,
                    'troops' => $this->number($club->troop_count),
                ])
                ->values()
                ->all(),
            'rank' => $summary['rank'],
            'direct_funds' => $this->funds($summary['direct_funds']),
            'indirect_funds' => $this->funds($summary['indirect_funds']),
            'total_donated' => ($summary['total_donated'] ?? 0) > 0
                ? '$'.format_number($summary['total_donated'], 2)
                : null,
            'donation_months' => ($summary['donation_months'] ?? 0) > 0 ? (int) $summary['donation_months'] : null,
            'milestones' => collect($summary['milestones'])
                ->map(fn (array $milestone): array => [
                    'description' => $milestone['description'],
                    'icon' => $milestone['icon'],
                    'is_501st' => $milestone['type'] === AchievementType::TROOPED_501,
                ])
                ->all(),
            'donor_image_url' => $this->is_active_donor ? config('tracker.donor.image_url') : null,
        ];
    }

    private function number(mixed $value): string
    {
        return $value == 0 ? '-' : format_number($value);
    }

    private function funds(mixed $value): string
    {
        return $value == 0 ? '-' : '$'.format_funds($value);
    }
}
