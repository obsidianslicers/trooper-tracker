<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData;

use App\Enums\EventStatus;
use App\Messages\Events\Resources\EventSummaryResource;
use App\Messages\Organizations\Queries\GetOrganizationsForPicker;
use App\Messages\Organizations\Resources\HostingOrganizationOptions;
use App\Messages\Reports\Queries\GetEventSummary;
use App\Models\Event;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * The completed events history (all of it, a page at a time) or the events cancelled over the
 * last 30 days, each with its totals and the squad filter's choices.
 *
 * @method static array call(Actor $actor, EventStatus $status)
 */
final class ListPastEventsPageData extends Message
{
    public const CANCELLED_LOOKBACK_DAYS = 30;

    private const PAGE_SIZE = 25;

    /** @param  Actor&Trooper  $actor */
    public function __construct(
        private readonly Actor $actor,
        private readonly EventStatus $status,
    ) {}

    public function handle(): array
    {
        $is_cancelled = $this->status === EventStatus::CANCELLED;

        $events = GetEventSummary::call(
            moderator: $this->actor,
            lookback: $is_cancelled ? self::CANCELLED_LOOKBACK_DAYS : null,
            show_all: true,
            status: $this->status,
            page_size: $is_cancelled ? null : self::PAGE_SIZE,
        );

        $rows = $events instanceof LengthAwarePaginator ? $events->getCollection() : $events;

        return [
            'status' => $this->status->value,
            'lookback_days' => $is_cancelled ? self::CANCELLED_LOOKBACK_DAYS : null,
            'events' => EventSummaryResource::collection($rows),
            'totals' => $this->getTotals($rows),
            'pagination' => $events instanceof LengthAwarePaginator ? $this->getPagination($events) : null,
            'hosting_organizations' => new HostingOrganizationOptions(
                GetOrganizationsForPicker::call(trooper: $this->actor, data: []),
            ),
            'has_map' => (bool) config('services.google.maps_api_key'),
        ];
    }

    /**
     * @param  Collection<int, Event>  $rows
     */
    private function getTotals(Collection $rows): array
    {
        return [
            'shifts' => (int) $rows->sum('event_shifts_count'),
            'unique_troopers' => (int) $rows->sum('unique_trooper_count'),
            'total_troopers' => (int) $rows->sum('total_trooper_count'),
            'direct_funds' => (float) $rows->sum('charity_direct_funds'),
            'indirect_funds' => (float) $rows->sum('charity_indirect_funds'),
        ];
    }

    /**
     * @param  LengthAwarePaginator<int, Event>  $events
     */
    private function getPagination(LengthAwarePaginator $events): array
    {
        return [
            'current_page' => $events->currentPage(),
            'last_page' => $events->lastPage(),
            'total' => $events->total(),
            'prev_url' => $events->previousPageUrl(),
            'next_url' => $events->nextPageUrl(),
        ];
    }
}
