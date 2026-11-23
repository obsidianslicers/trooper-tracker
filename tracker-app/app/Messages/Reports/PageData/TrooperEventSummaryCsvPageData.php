<?php

declare(strict_types=1);

namespace App\Messages\Reports\PageData;

use App\Messages\Concerns\ResolvesReportFilters;
use App\Messages\Reports\Queries\GetTrooperEventSummary;
use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The Trooper Event Counts report as CSV rows: every trooper that matches the filters, then the totals.
 *
 * @method static array{filename: string, rows: array<int, array<int, mixed>>} call(Actor $actor, ?string $date_start = null, ?string $date_end = null, ?string $active_only = null, ?int $organization_id = null, ?string $sort = null, ?string $dir = null)
 */
final class TrooperEventSummaryCsvPageData extends Message
{
    use ResolvesReportFilters;

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
     * @return array{filename: string, rows: array<int, array<int, mixed>>}
     */
    public function handle(): array
    {
        $date_start = $this->startOfDay($this->date_start);
        $date_end = $this->endOfDay($this->date_end);
        $active_only = (bool) $this->active_only;

        $organizations = $this->moderatedOrganizations($this->actor, Organization::NAME, ['id', 'name', 'node_path']);
        $organization = $this->chosenOrganization($organizations, $this->organization_id);

        $trooper_events = GetTrooperEventSummary::call(
            moderator: $this->actor,
            date_start: $date_start,
            date_end: $date_end,
            active_only: $active_only,
            page_size: PHP_INT_MAX,
            organization: $organization,
            sort: $this->sort ?? 'event_shifts_count',
            dir: $this->dir ?? 'desc',
            accessible_org_ids: $this->rootOrganizationIds($organizations),
        );

        $meta = array_filter([
            $organization ? 'Club: '.$organization->name : null,
            $date_start ? 'From: '.$date_start->format('Y-m-d') : null,
            $date_end ? 'To: '.$date_end->format('Y-m-d') : null,
            $active_only ? 'Active Members Only' : null,
        ]);

        $rows = $meta === [] ? [] : [array_values($meta)];
        $rows[] = ['Trooper', 'Unique Events', 'Total Shifts'];

        foreach ($trooper_events as $trooper_event)
        {
            $rows[] = [$trooper_event->display_name, $trooper_event->events_count, $trooper_event->event_shifts_count];
        }

        $rows[] = [];
        $rows[] = ['Total', $trooper_events->sum('events_count'), $trooper_events->sum('event_shifts_count')];

        return [
            'filename' => 'trooper-event-summary-'.now()->format('Y-m-d').'.csv',
            'rows' => $rows,
        ];
    }
}
