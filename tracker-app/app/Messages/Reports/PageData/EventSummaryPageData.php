<?php

declare(strict_types=1);

namespace App\Messages\Reports\PageData;

use App\Messages\Reports\Queries\GetEventSummary;
use App\Messages\Reports\Resources\EventSummaryRowResource;
use App\Messages\Reports\Resources\EventSummaryTotalsResource;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Data for the Event Summary report: closed events of the last 30 days, with who came and the
 * money raised.
 *
 * @method static array<string, mixed> call(Actor $actor)
 */
final class EventSummaryPageData extends Message
{
    public const DAYS = 30;

    /**
     * @param  Actor&Trooper  $actor
     */
    public function __construct(private readonly Actor $actor) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $events = GetEventSummary::call(moderator: $this->actor, lookback: self::DAYS);

        return [
            'days' => self::DAYS,
            'events' => EventSummaryRowResource::collection($events->values()),
            'totals' => new EventSummaryTotalsResource($events),
        ];
    }
}
