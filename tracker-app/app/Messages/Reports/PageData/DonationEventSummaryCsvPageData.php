<?php

declare(strict_types=1);

namespace App\Messages\Reports\PageData;

use App\Messages\Concerns\ResolvesDonationReportClubs;
use App\Messages\Reports\Queries\GetDonationEventSummary;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Carbon\Carbon;
use Generator;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use Illuminate\Support\LazyCollection;

/**
 * The Donation Event Summary as CSV rows: a row per shift, then the totals.
 *
 * The events are read a page at a time while the rows are written, so a large export never
 * holds every event in memory.
 *
 * @method static array{filename: string, rows: LazyCollection<int, array<int, mixed>>} call(Actor $actor, ?string $date_start = null, ?string $date_end = null, ?string $charity_only = null, array|string|null $organization_ids = null, ?string $sort = null, ?string $dir = null)
 */
final class DonationEventSummaryCsvPageData extends Message
{
    use ResolvesDonationReportClubs;

    public const PAGE_SIZE = 500;

    public const HEADINGS = [
        'Event', 'Date', 'Club', 'Shift', 'Charity', 'Direct Funds', 'Indirect Funds', 'Total Funds',
        'Hours', 'Attendees', 'Trooper Hours', 'Notes',
    ];

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
     * @return array{filename: string, rows: LazyCollection<int, array<int, mixed>>}
     */
    public function handle(): array
    {
        $date_start = $this->startOfDay($this->date_start);
        $date_end = $this->endOfDay($this->date_end);
        $clubs = $this->donationClubs($this->actor, (array) $this->organization_ids);

        $club_names = $clubs['filter_orgs']->whereIn('id', $clubs['selected_ids'])->pluck('name')->join(', ');

        $meta = array_values(array_filter([
            $club_names !== '' ? 'Club: '.$club_names : null,
            $date_start ? 'From: '.$date_start->format('Y-m-d') : null,
            $date_end ? 'To: '.$date_end->format('Y-m-d') : null,
            $this->charity_only ? 'Charity Data Only' : null,
        ]));

        return [
            'filename' => 'donation-event-summary-'.now()->format('Y-m-d').'.csv',
            'rows' => LazyCollection::make(function () use ($meta, $date_start, $date_end, $clubs)
            {
                if ($meta !== [])
                {
                    yield $meta;
                }

                yield self::HEADINGS;

                $shift_rows = $this->shiftRows(
                    $date_start,
                    $date_end,
                    $clubs['selected_org_ids'],
                    $clubs['accessible_org_ids'],
                );

                foreach ($shift_rows as $row)
                {
                    yield $row;
                }
            }),
        ];
    }

    /**
     * @param  array<int, int>  $selected_org_ids
     * @param  array<int, int>  $accessible_org_ids
     * @return Generator<int, array<int, mixed>>
     */
    private function shiftRows(?Carbon $date_start, ?Carbon $date_end, array $selected_org_ids, array $accessible_org_ids): Generator
    {
        $totals = ['direct' => 0, 'indirect' => 0, 'hours' => 0, 'attended' => 0, 'trooper_hours' => 0];
        $page = 1;

        do
        {
            $events = GetDonationEventSummary::call(
                moderator: $this->actor,
                date_start: $date_start,
                date_end: $date_end,
                charity_only: (bool) $this->charity_only,
                page_size: self::PAGE_SIZE,
                sort: $this->sort ?? 'event_start',
                dir: $this->dir ?? 'desc',
                selected_org_ids: $selected_org_ids,
                accessible_org_ids: $accessible_org_ids,
                page: $page,
            );

            foreach ($events as $event)
            {
                foreach ($event->event_shifts as $shift)
                {
                    yield $this->shiftRow($event, $shift, $totals);
                }
            }

            $page++;
        }
        while ($events->hasMorePages());

        yield [];
        yield [
            'Total', '', '', '', '',
            $totals['direct'],
            $totals['indirect'],
            $totals['direct'] + $totals['indirect'],
            $totals['hours'],
            $totals['attended'],
            $totals['trooper_hours'],
            '',
        ];
    }

    /**
     * @param  array<string, int|float>  $totals
     * @return array<int, mixed>
     */
    private function shiftRow(Event $event, EventShift $shift, array &$totals): array
    {
        $direct = $shift->charity_direct_funds;
        $indirect = $shift->charity_indirect_funds;
        $hours = $shift->effective_charity_hours;
        $attended = $shift->attendees_count;
        $trooper_hours = $hours * $attended;

        $totals['direct'] += $direct;
        $totals['indirect'] += $indirect;
        $totals['hours'] += $hours;
        $totals['attended'] += $attended;
        $totals['trooper_hours'] += $trooper_hours;

        return [
            $event->name,
            $event->event_start?->format('Y-m-d'),
            $event->organization?->name,
            $shift->compact_time_display,
            $shift->charity_name,
            $direct,
            $indirect,
            $direct + $indirect,
            $hours,
            $attended,
            $trooper_hours,
            $shift->charity_notes,
        ];
    }
}
