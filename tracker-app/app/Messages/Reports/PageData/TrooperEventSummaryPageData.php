<?php

declare(strict_types=1);

namespace App\Messages\Reports\PageData;

use App\Messages\Concerns\DescribesReportFilters;
use App\Messages\Concerns\ResolvesReportFilters;
use App\Messages\Reports\Queries\GetTrooperEventSummary;
use App\Messages\Reports\Resources\TrooperEventSummaryRowResource;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Data for the Trooper Event Counts report: one page of troopers and the filters that found them.
 *
 * @method static array<string, mixed> call(Actor $actor, ?string $date_start = null, ?string $date_end = null, ?string $active_only = null, ?int $organization_id = null, ?string $sort = null, ?string $dir = null)
 */
final class TrooperEventSummaryPageData extends Message
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
        private readonly ?string $active_only = null,
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
        $active_only = (bool) $this->active_only;
        $sort = $this->sort ?? 'event_shifts_count';
        $dir = $this->dir ?? 'desc';

        $organizations = $this->moderatedOrganizations($this->actor, Organization::NAME, ['id', 'name', 'node_path']);
        $organization = $this->chosenOrganization($organizations, $this->organization_id);

        $troopers = GetTrooperEventSummary::call(
            moderator: $this->actor,
            date_start: $date_start,
            date_end: $date_end,
            active_only: $active_only,
            page_size: self::PAGE_SIZE,
            organization: $organization,
            sort: $sort,
            dir: $dir,
            accessible_org_ids: $this->rootOrganizationIds($organizations),
        );

        return [
            'filters' => [
                'organization_id' => $organization?->id,
                'date_start' => $date_start?->format('Y-m-d'),
                'date_end' => $date_end?->format('Y-m-d'),
                'active_only' => $active_only,
            ],
            'organizations' => $organizations
                ->map(fn (Organization $organization): array => [
                    'value' => $organization->id,
                    'label' => $organization->name,
                ])
                ->values()
                ->all(),
            'caption' => $this->caption($organization, $date_start, $date_end, $active_only),
            'sorts' => $this->sortLinks(['display_name', 'events_count', 'event_shifts_count'], $sort, $dir),
            'csv_url' => $this->csvUrl(),
            'troopers' => TrooperEventSummaryRowResource::collection($troopers),
        ];
    }

    private function caption(
        ?Organization $organization,
        ?Carbon $date_start,
        ?Carbon $date_end,
        bool $active_only,
    ): string {
        $club = $organization?->name ?? __('admin.All Clubs');

        $caption = $club.' — '.$this->dateRange($date_start, $date_end);

        return $active_only ? $caption.' '.__('admin.— Active Members Only') : $caption;
    }
}
