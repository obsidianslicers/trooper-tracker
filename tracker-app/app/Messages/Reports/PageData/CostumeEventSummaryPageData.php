<?php

declare(strict_types=1);

namespace App\Messages\Reports\PageData;

use App\Messages\Concerns\DescribesReportFilters;
use App\Messages\Concerns\FormatsReportNumbers;
use App\Messages\Reports\Resources\CostumeEventSummaryRowResource;
use App\Models\Organization;
use Carbon\Carbon;
use Hyperdrive\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Data for the Costume Event Summary report: one page of costumes, their totals, and the filters.
 *
 * @method static array<string, mixed> call(LengthAwarePaginator $costumes, Collection $organizations, ?int $organization_id, ?Carbon $date_start, ?Carbon $date_end, string $sort, string $dir)
 */
final class CostumeEventSummaryPageData extends Message
{
    use DescribesReportFilters;
    use FormatsReportNumbers;

    /**
     * @param  Collection<int, Organization>  $organizations
     */
    public function __construct(
        private readonly LengthAwarePaginator $costumes,
        private readonly Collection $organizations,
        private readonly ?int $organization_id,
        private readonly ?Carbon $date_start,
        private readonly ?Carbon $date_end,
        private readonly string $sort,
        private readonly string $dir,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $rows = collect($this->costumes->items());

        return [
            'filters' => [
                'organization_id' => $this->organization_id,
                'date_start' => $this->date_start?->format('Y-m-d'),
                'date_end' => $this->date_end?->format('Y-m-d'),
            ],
            'organizations' => $this->organizations
                ->map(fn (Organization $organization): array => [
                    'value' => $organization->id,
                    'label' => $organization->name,
                ])
                ->values()
                ->all(),
            'caption' => $this->caption(),
            'sorts' => $this->sortLinks(['name', 'events_count', 'uses_count'], $this->sort, $this->dir),
            'csv_url' => $this->csvUrl(),
            'costumes' => CostumeEventSummaryRowResource::collection($this->costumes),
            'totals' => $rows->isEmpty() ? null : [
                'events_count' => $this->count($rows->sum('events_count')),
                'uses_count' => $this->count($rows->sum('uses_count')),
            ],
        ];
    }

    private function caption(): string
    {
        $club = $this->organization_id
            ? $this->organizations->firstWhere('id', $this->organization_id)?->name
            : __('admin.All Clubs');

        return $club.' — '.$this->dateRange($this->date_start, $this->date_end);
    }
}
