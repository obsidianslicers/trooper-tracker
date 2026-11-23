<?php

declare(strict_types=1);

namespace App\Messages\Reports\PageData;

use App\Messages\Reports\Queries\GetEventTypeCount;
use App\Messages\Reports\Resources\EventTypeCountRowResource;
use App\Messages\Reports\Resources\EventTypeCountTotalsResource;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Data for the Event Type Counts report: closed events of each type in the last 30 days.
 *
 * @method static array<string, mixed> call(Actor $actor)
 */
final class EventTypeCountPageData extends Message
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
        $types = GetEventTypeCount::call(moderator: $this->actor, lookback: self::DAYS);

        return [
            'days' => self::DAYS,
            'event_types' => EventTypeCountRowResource::collection($types->values()),
            'totals' => new EventTypeCountTotalsResource($types),
        ];
    }
}
