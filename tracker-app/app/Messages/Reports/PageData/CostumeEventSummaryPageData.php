<?php

declare(strict_types=1);

namespace App\Messages\Reports\PageData;

use App\Messages\Concerns\DescribesReportFilters;
use App\Messages\Concerns\FormatsReportNumbers;
use App\Messages\Concerns\ResolvesReportFilters;
use App\Messages\Reports\Queries\GetCostumeEventSummary;
use App\Messages\Reports\Resources\CostumeEventSummaryRowResource;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Data for the Costume Event Summary report: one page of costumes, their totals, and the filters.
 *
 * @method static array<string, mixed> call(Actor $actor, ?string $date_start = null, ?string $date_end = null, ?int $organization_id = null, ?string $sort = null, ?string $dir = null)
 */
final class CostumeEventSummaryPageData extends Message
{
    use DescribesReportFilters;
    use FormatsReportNumbers;
    use ResolvesReportFilters;

    public const PAGE_SIZE = 50;

    /**
     * @param  Actor&Trooper  $actor
     */
    public function __construct(
        private readonly Actor $actor,
        private readonly ?string $date_start = null,
        private readonly ?string $date_end = null,
        private readonly ?int $organization_id = null,
        private readonly ?string $sort = null,
        private readonly ?string $dir = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $date_start = $this->startOfDay($this->date_start);
        $date_end = $this->endOfDay($this->date_end);
        $sort = $this->sort ?? 'uses_count';
        $dir = $this->dir ?? 'desc';

        $organizations = $this->moderatedOrganizations($this->actor, Organization::NAME, ['id', 'name', 'node_path']);
        $organization = $this->chosenOrganization($organizations, $this->organization_id);

        $costumes = GetCostumeEventSummary::call(
            moderator: $this->actor,
            date_start: $date_start,
            date_end: $date_end,
            page_size: self::PAGE_SIZE,
            organization: $organization,
            sort: $sort,
            dir: $dir,
            accessible_org_ids: $this->rootOrganizationIds($organizations),
        );

        $rows = collect($costumes->items());

        return [
            'filters' => [
                'organization_id' => $organization?->id,
                'date_start' => $date_start?->format('Y-m-d'),
                'date_end' => $date_end?->format('Y-m-d'),
            ],
            'organizations' => $this->organizationOptions($organizations),
            'caption' => $this->caption($organization, $date_start, $date_end),
            'sorts' => $this->sortLinks(['name', 'events_count', 'uses_count'], $sort, $dir),
            'csv_url' => $this->csvUrl(),
            'costumes' => CostumeEventSummaryRowResource::collection($costumes),
            'totals' => $rows->isEmpty() ? null : [
                'events_count' => $this->count($rows->sum('events_count')),
                'uses_count' => $this->count($rows->sum('uses_count')),
            ],
        ];
    }

    /**
     * @param  Collection<int, Organization>  $organizations
     * @return array<int, array{value: int, label: string}>
     */
    private function organizationOptions(Collection $organizations): array
    {
        return $organizations
            ->map(fn (Organization $organization): array => [
                'value' => $organization->id,
                'label' => $organization->name,
            ])
            ->values()
            ->all();
    }

    private function caption(?Organization $organization, ?Carbon $date_start, ?Carbon $date_end): string
    {
        $club = $organization?->name ?? __('admin.All Clubs');

        return $club.' — '.$this->dateRange($date_start, $date_end);
    }
}
