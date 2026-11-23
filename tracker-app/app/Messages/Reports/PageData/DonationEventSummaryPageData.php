<?php

declare(strict_types=1);

namespace App\Messages\Reports\PageData;

use App\Messages\Concerns\DescribesReportFilters;
use App\Messages\Concerns\ResolvesDonationReportClubs;
use App\Messages\Reports\Queries\GetDonationEventSummary;
use App\Messages\Reports\Resources\DonationEventRowResource;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Data for the Donation Event Summary report: one page of events with what each shift raised.
 *
 * @method static array<string, mixed> call(Actor $actor, ?string $date_start = null, ?string $date_end = null, ?string $charity_only = null, array|string|null $organization_ids = null, ?string $sort = null, ?string $dir = null)
 */
final class DonationEventSummaryPageData extends Message
{
    use DescribesReportFilters;
    use ResolvesDonationReportClubs;

    public const PAGE_SIZE = 50;

    public const SORTS = [
        'event_start' => 'admin.Date',
        'name' => 'admin.Event Name',
        'charity_direct_funds' => 'admin.Direct Funds',
        'charity_indirect_funds' => 'admin.Indirect Funds',
        'charity_hours' => 'admin.Hours',
        'attendees_count' => 'admin.Attendees',
    ];

    /** @var Collection<int, Organization> */
    private Collection $filter_orgs;

    /** @var array<int, int> */
    private array $selected_ids = [];

    private ?Carbon $starts = null;

    private ?Carbon $ends = null;

    /**
     * @param  Actor&Trooper  $actor
     * @param  array<int, mixed>|string|null  $organization_ids
     */
    public function __construct(
        private readonly Actor $actor,
        private readonly ?string $date_start = null,
        private readonly ?string $date_end = null,
        private readonly ?string $charity_only = null,
        private readonly array|string|null $organization_ids = null,
        private readonly ?string $sort = null,
        private readonly ?string $dir = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $this->starts = $this->startOfDay($this->date_start);
        $this->ends = $this->endOfDay($this->date_end);
        $clubs = $this->donationClubs($this->actor, (array) $this->organization_ids);
        $this->filter_orgs = $clubs['filter_orgs'];
        $this->selected_ids = $clubs['selected_ids'];

        $events = $this->events($clubs['selected_org_ids'], $clubs['accessible_org_ids']);
        $rows = collect($events->items());

        return [
            'filters' => [
                'date_start' => $this->starts?->format('Y-m-d'),
                'date_end' => $this->ends?->format('Y-m-d'),
                'charity_only' => $this->isCharityOnly(),
                'organization_ids' => $this->selectedOrDefault(),
                'sort' => $this->sortColumn(),
                'dir' => $this->sortDirection(),
            ],
            'clubs' => $this->clubOptions(),
            'sort_options' => collect(self::SORTS)
                ->map(fn (string $key, string $value): array => ['value' => $value, 'label' => __($key)])
                ->values()
                ->all(),
            'caption' => $this->caption(),
            'csv_url' => $this->csvUrl(),
            'events' => DonationEventRowResource::collection($events),
            'grand_totals' => $rows->isEmpty() ? null : DonationEventRowResource::grandTotals($rows),
        ];
    }

    /**
     * @param  array<int, int>  $selected_org_ids
     * @param  array<int, int>  $accessible_org_ids
     */
    private function events(array $selected_org_ids, array $accessible_org_ids): LengthAwarePaginator
    {
        return GetDonationEventSummary::call(
            moderator: $this->actor,
            date_start: $this->starts,
            date_end: $this->ends,
            charity_only: $this->isCharityOnly(),
            page_size: self::PAGE_SIZE,
            sort: $this->sortColumn(),
            dir: $this->sortDirection(),
            selected_org_ids: $selected_org_ids,
            accessible_org_ids: $accessible_org_ids,
        );
    }

    private function isCharityOnly(): bool
    {
        return (bool) $this->charity_only;
    }

    private function sortColumn(): string
    {
        return $this->sort ?? 'event_start';
    }

    private function sortDirection(): string
    {
        return $this->dir ?? 'desc';
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function clubOptions(): array
    {
        if ($this->filter_orgs->count() <= 1)
        {
            return [];
        }

        return $this->filter_orgs
            ->map(fn (Organization $organization): array => [
                'value' => $organization->id,
                'label' => $organization->name,
            ])
            ->values()
            ->all();
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
        if (!$this->starts && !$this->ends && !$this->isCharityOnly() && empty($this->selected_ids))
        {
            return null;
        }

        $parts = [];

        if (!empty($this->selected_ids))
        {
            $parts[] = $this->filter_orgs->whereIn('id', $this->selected_ids)->pluck('name')->join(', ');
        }

        if ($this->starts)
        {
            $parts[] = '— '.__('admin.From :date', ['date' => $this->starts->localFormat('M d, Y')]);
        }

        if ($this->ends)
        {
            $parts[] = __('admin.to :date', ['date' => $this->ends->localFormat('M d, Y')]);
        }

        if ($this->isCharityOnly())
        {
            $parts[] = __('admin.— Charity data only');
        }

        return implode(' ', $parts);
    }
}
