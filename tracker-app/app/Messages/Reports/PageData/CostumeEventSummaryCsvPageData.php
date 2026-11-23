<?php

declare(strict_types=1);

namespace App\Messages\Reports\PageData;

use App\Messages\Concerns\ResolvesReportFilters;
use App\Messages\Reports\Queries\GetCostumeEventSummary;
use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The Costume Event Summary as CSV rows: every costume that matches the filters, then the totals.
 *
 * @method static array{filename: string, rows: array<int, array<int, mixed>>} call(Actor $actor, ?string $date_start = null, ?string $date_end = null, ?int $organization_id = null, ?string $sort = null, ?string $dir = null)
 */
final class CostumeEventSummaryCsvPageData extends Message
{
    use ResolvesReportFilters;

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
     * @return array{filename: string, rows: array<int, array<int, mixed>>}
     */
    public function handle(): array
    {
        $date_start = $this->startOfDay($this->date_start);
        $date_end = $this->endOfDay($this->date_end);

        $organizations = $this->moderatedOrganizations($this->actor, Organization::NAME, ['id', 'name', 'node_path']);
        $organization = $this->chosenOrganization($organizations, $this->organization_id);

        $costume_events = GetCostumeEventSummary::call(
            moderator: $this->actor,
            date_start: $date_start,
            date_end: $date_end,
            page_size: PHP_INT_MAX,
            organization: $organization,
            sort: $this->sort ?? 'uses_count',
            dir: $this->dir ?? 'desc',
            accessible_org_ids: $this->rootOrganizationIds($organizations),
        );

        $meta = array_filter([
            $organization ? 'Club: '.$organization->name : null,
            $date_start ? 'From: '.$date_start->format('Y-m-d') : null,
            $date_end ? 'To: '.$date_end->format('Y-m-d') : null,
        ]);

        $rows = $meta === [] ? [] : [array_values($meta)];
        $rows[] = ['Costume', 'Unique Events', 'Total Uses'];

        foreach ($costume_events as $costume_event)
        {
            $rows[] = [$costume_event->name, $costume_event->events_count, $costume_event->uses_count];
        }

        $rows[] = [];
        $rows[] = ['Total', $costume_events->sum('events_count'), $costume_events->sum('uses_count')];

        return [
            'filename' => 'costume-event-summary-'.now()->format('Y-m-d').'.csv',
            'rows' => $rows,
        ];
    }
}
