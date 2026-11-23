<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData;

use App\Enums\EventStatus;
use App\Messages\Events\Resources\EventSummaryResource;
use App\Messages\Events\Resources\PastEventsPaginationResource;
use App\Messages\Events\Resources\PastEventTotalsResource;
use App\Messages\Organizations\Queries\GetOrganizationsForPicker;
use App\Messages\Organizations\Resources\HostingOrganizationOptions;
use App\Messages\Reports\Queries\GetEventSummary;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use Illuminate\Pagination\LengthAwarePaginator;

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

        $is_paged = $events instanceof LengthAwarePaginator;
        $rows = $is_paged ? $events->getCollection() : $events;

        return [
            'status' => $this->status->value,
            'lookback_days' => $is_cancelled ? self::CANCELLED_LOOKBACK_DAYS : null,
            'events' => EventSummaryResource::collection($rows),
            'totals' => new PastEventTotalsResource($rows),
            'pagination' => $is_paged ? new PastEventsPaginationResource($events) : null,
            'hosting_organizations' => new HostingOrganizationOptions(
                GetOrganizationsForPicker::call(trooper: $this->actor, data: []),
            ),
            'has_map' => (bool) config('services.google.maps_api_key'),
        ];
    }
}
