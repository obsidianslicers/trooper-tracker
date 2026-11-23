<?php

declare(strict_types=1);

namespace App\Messages\Reports\PageData;

use App\Messages\Concerns\DescribesReportFilters;
use App\Messages\Concerns\ResolvesReportFilters;
use App\Messages\Reports\Queries\GetCostumeEventSummary;
use App\Messages\Reports\Resources\ClubReportFiltersResource;
use App\Messages\Reports\Resources\CostumeEventSummaryRowResource;
use App\Messages\Reports\Resources\CostumeEventSummaryTotalsResource;
use App\Messages\Reports\Resources\ReportClubOptions;
use App\Messages\Reports\Resources\ReportSortLinks;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Data for the Costume Event Summary report: one page of costumes, their totals, and the filters.
 *
 * @method static array<string, mixed> call(Actor $actor, ?string $date_start = null, ?string $date_end = null, ?int $organization_id = null, ?string $sort = null, ?string $dir = null)
 */
final class CostumeEventSummaryPageData extends Message
{
    use DescribesReportFilters;
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
            'filters' => new ClubReportFiltersResource([
                'organization' => $organization,
                'date_start' => $date_start,
                'date_end' => $date_end,
            ]),
            'organizations' => new ReportClubOptions($organizations),
            'caption' => $this->caption($organization, $date_start, $date_end),
            'sorts' => new ReportSortLinks(['name', 'events_count', 'uses_count'], $sort, $dir),
            'csv_url' => $this->csvUrl(),
            'costumes' => CostumeEventSummaryRowResource::collection($costumes),
            'totals' => $rows->isEmpty() ? null : new CostumeEventSummaryTotalsResource($rows),
        ];
    }

    private function caption(?Organization $organization, ?Carbon $date_start, ?Carbon $date_end): string
    {
        $club = $organization?->name ?? __('admin.All Clubs');

        return $club.' — '.$this->dateRange($date_start, $date_end);
    }
}
