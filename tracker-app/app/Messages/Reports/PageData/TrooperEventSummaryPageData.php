<?php

declare(strict_types=1);

namespace App\Messages\Reports\PageData;

use App\Messages\Concerns\DescribesReportFilters;
use App\Messages\Reports\Resources\TrooperEventSummaryRowResource;
use App\Models\Organization;
use Carbon\Carbon;
use Hyperdrive\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Data for the Trooper Event Counts report: one page of troopers and the filters that found them.
 *
 * @method static array<string, mixed> call(LengthAwarePaginator $troopers, Collection $organizations, ?int $organization_id, ?Carbon $date_start, ?Carbon $date_end, bool $active_only, string $sort, string $dir)
 */
final class TrooperEventSummaryPageData extends Message
{
    use DescribesReportFilters;

    /**
     * @param  Collection<int, Organization>  $organizations
     */
    public function __construct(
        private readonly LengthAwarePaginator $troopers,
        private readonly Collection $organizations,
        private readonly ?int $organization_id,
        private readonly ?Carbon $date_start,
        private readonly ?Carbon $date_end,
        private readonly bool $active_only,
        private readonly string $sort,
        private readonly string $dir,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return [
            'filters' => [
                'organization_id' => $this->organization_id,
                'date_start' => $this->date_start?->format('Y-m-d'),
                'date_end' => $this->date_end?->format('Y-m-d'),
                'active_only' => $this->active_only,
            ],
            'organizations' => $this->organizations
                ->map(fn (Organization $organization): array => [
                    'value' => $organization->id,
                    'label' => $organization->name,
                ])
                ->values()
                ->all(),
            'caption' => $this->caption(),
            'sorts' => $this->sortLinks(['display_name', 'events_count', 'event_shifts_count'], $this->sort, $this->dir),
            'csv_url' => $this->csvUrl(),
            'troopers' => TrooperEventSummaryRowResource::collection($this->troopers),
        ];
    }

    private function caption(): string
    {
        $club = $this->organization_id
            ? $this->organizations->firstWhere('id', $this->organization_id)?->name
            : __('admin.All Clubs');

        $caption = $club.' — '.$this->dateRange($this->date_start, $this->date_end);

        return $this->active_only ? $caption.' '.__('admin.— Active Members Only') : $caption;
    }
}
