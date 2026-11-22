<?php

declare(strict_types=1);

namespace App\Messages\Reports\PageData;

use App\Messages\Concerns\DescribesReportFilters;
use App\Messages\Reports\Resources\DonationEventRowResource;
use App\Models\Organization;
use Carbon\Carbon;
use Hyperdrive\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Data for the Donation Event Summary report: one page of events with what each shift raised.
 *
 * @method static array<string, mixed> call(LengthAwarePaginator $events, Collection $filter_orgs, array $selected_ids, ?Carbon $date_start, ?Carbon $date_end, bool $charity_only, string $sort, string $dir)
 */
final class DonationEventSummaryPageData extends Message
{
    use DescribesReportFilters;

    public const SORTS = [
        'event_start' => 'admin.Date',
        'name' => 'admin.Event Name',
        'charity_direct_funds' => 'admin.Direct Funds',
        'charity_indirect_funds' => 'admin.Indirect Funds',
        'charity_hours' => 'admin.Hours',
        'attendees_count' => 'admin.Attendees',
    ];

    /**
     * @param  Collection<int, Organization>  $filter_orgs
     * @param  array<int, int>  $selected_ids
     */
    public function __construct(
        private readonly LengthAwarePaginator $events,
        private readonly Collection $filter_orgs,
        private readonly array $selected_ids,
        private readonly ?Carbon $date_start,
        private readonly ?Carbon $date_end,
        private readonly bool $charity_only,
        private readonly string $sort,
        private readonly string $dir,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $events = collect($this->events->items());

        return [
            'filters' => [
                'date_start' => $this->date_start?->format('Y-m-d'),
                'date_end' => $this->date_end?->format('Y-m-d'),
                'charity_only' => $this->charity_only,
                'organization_ids' => $this->selectedOrDefault(),
                'sort' => $this->sort,
                'dir' => $this->dir,
            ],
            'clubs' => $this->filter_orgs->count() > 1
                ? $this->filter_orgs
                    ->map(fn (Organization $organization): array => [
                        'value' => $organization->id,
                        'label' => $organization->name,
                    ])
                    ->values()
                    ->all()
                : [],
            'sort_options' => collect(self::SORTS)
                ->map(fn (string $key, string $value): array => ['value' => $value, 'label' => __($key)])
                ->values()
                ->all(),
            'caption' => $this->caption(),
            'csv_url' => $this->csvUrl(),
            'events' => DonationEventRowResource::collection($this->events),
            'grand_totals' => $events->isEmpty() ? null : DonationEventRowResource::grandTotals($events),
        ];
    }

    /**
     * Every club starts ticked; an empty selection means all of them.
     *
     * @return array<int, int>
     */
    private function selectedOrDefault(): array
    {
        return empty($this->selected_ids)
            ? $this->filter_orgs->pluck('id')->map(fn ($id): int => (int) $id)->all()
            : $this->selected_ids;
    }

    private function caption(): ?string
    {
        if (!$this->date_start && !$this->date_end && !$this->charity_only && empty($this->selected_ids))
        {
            return null;
        }

        $parts = [];

        if (!empty($this->selected_ids))
        {
            $parts[] = $this->filter_orgs->whereIn('id', $this->selected_ids)->pluck('name')->join(', ');
        }

        if ($this->date_start)
        {
            $parts[] = '— '.__('admin.From :date', ['date' => $this->date_start->localFormat('M d, Y')]);
        }

        if ($this->date_end)
        {
            $parts[] = __('admin.to :date', ['date' => $this->date_end->localFormat('M d, Y')]);
        }

        if ($this->charity_only)
        {
            $parts[] = __('admin.— Charity data only');
        }

        return implode(' ', $parts);
    }
}
